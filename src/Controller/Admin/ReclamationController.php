<?php

namespace App\Controller\Admin;

use App\Entity\Reclamation;
use App\Entity\ReclamationResponse;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use App\Repository\ReclamationResponseRepository;
use App\Repository\UserRepository;
use App\Service\ReclamationHistoriquePdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reclamations')]
class ReclamationController extends AbstractController
{
    #[Route('', name: 'app_admin_reclamations', methods: ['GET'])]
    public function index(
        Request $request,
        ReclamationRepository $reclamationRepository,
        ReclamationResponseRepository $reclamationResponseRepository,
    ): Response {
        $vars = $this->buildReclamationListContext($request, $reclamationRepository, $reclamationResponseRepository);

        if ($request->query->getBoolean('partial')) {
            return $this->render('admin/reclamation/_table_rows.html.twig', $vars);
        }

        return $this->render('admin/reclamation/index.html.twig', $vars);
    }

    #[Route('/historique.pdf', name: 'app_admin_reclamations_historique_pdf', methods: ['GET'])]
    public function historiquePdf(
        Request $request,
        ReclamationRepository $reclamationRepository,
        ReclamationResponseRepository $reclamationResponseRepository,
        ReclamationHistoriquePdfGenerator $pdfGenerator,
    ): Response {
        $ctx = $this->buildReclamationListContext($request, $reclamationRepository, $reclamationResponseRepository);

        $pdfBinary = $pdfGenerator->buildPdfContent(
            $ctx['reclamations'],
            $ctx['responses_by_reclamation'],
            [
                'q' => $ctx['filter_q'],
                'statut' => $ctx['filter_statut'],
                'priorite' => $ctx['filter_priorite'],
            ],
        );

        $filename = 'historique-reclamations-'.(new \DateTimeImmutable())->format('Y-m-d-His').'.pdf';

        return new Response($pdfBinary, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (new ResponseHeaderBag())->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename,
                'historique-reclamations.pdf',
            ),
        ]);
    }

    /**
     * @return array{
     *     reclamations: list<Reclamation>,
     *     filter_q: string,
     *     filter_statut: string,
     *     filter_priorite: string,
     *     responses_by_reclamation: array<int, list<ReclamationResponse>>,
     *     reponses_historique_b64: array<int, string>
     * }
     */
    private function buildReclamationListContext(
        Request $request,
        ReclamationRepository $reclamationRepository,
        ReclamationResponseRepository $reclamationResponseRepository,
    ): array {
        $q = $request->query->getString('q', '');
        $statut = $request->query->getString('statut', '');
        $priorite = $request->query->getString('priorite', '');

        $reclamations = $reclamationRepository->searchAndFilter(
            '' !== $q ? $q : null,
            '' !== $statut ? $statut : null,
            '' !== $priorite ? $priorite : null,
        );

        $responsesByReclamation = $reclamationResponseRepository->findGroupedByReclamations($reclamations);

        $reponsesHistoriqueB64 = [];
        foreach ($reclamations as $r) {
            $id = $r->getId();
            if (null === $id) {
                continue;
            }
            $payload = [];
            foreach ($responsesByReclamation[$id] ?? [] as $rr) {
                $admin = $rr->getAdmin();
                $payload[] = [
                    'date' => $rr->getDateResponse()?->format('d/m/Y H:i') ?? '—',
                    'admin' => null !== $admin ? $admin->getPrenom().' '.$admin->getNom() : '—',
                    'contenu' => $rr->getContenu(),
                ];
            }
            try {
                $reponsesHistoriqueB64[$id] = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            } catch (\JsonException) {
                $reponsesHistoriqueB64[$id] = base64_encode('[]');
            }
        }

        return [
            'reclamations' => $reclamations,
            'filter_q' => $q,
            'filter_statut' => $statut,
            'filter_priorite' => $priorite,
            'responses_by_reclamation' => $responsesByReclamation,
            'reponses_historique_b64' => $reponsesHistoriqueB64,
        ];
    }

    #[Route('/nouveau', name: 'app_admin_reclamations_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();
        $reclamation->setDateCreation(new \DateTime());

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $reclamation->getDateCreation()) {
                $reclamation->setDateCreation(new \DateTime());
            }
            $em->persist($reclamation);
            $em->flush();
            $this->addFlash('success', 'La réclamation a été enregistrée.');

            return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reclamation/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/repondre', name: 'app_admin_reclamations_respond', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function respond(
        Request $request,
        Reclamation $reclamation,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserRepository $userRepository,
    ): Response {
        if (!$this->isCsrfTokenValid('repondre'.$reclamation->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide. Réessayez depuis la liste des réclamations.');

            return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        if ('Traité' === $reclamation->getStatut()) {
            $this->addFlash('error', 'Cette réclamation est déjà traitée.');

            return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        $contenu = trim($request->request->getString('contenu'));
        if (strlen($contenu) < 10) {
            $this->addFlash('error', 'La réponse doit contenir au moins 10 caractères.');

            return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        $admin = $userRepository->findOneAdmin();
        if (null === $admin) {
            $this->addFlash('error', 'Aucun utilisateur avec le rôle admin trouvé pour enregistrer la réponse.');

            return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        $now = new \DateTime();
        $responseRow = new ReclamationResponse();
        $responseRow->setReclamation($reclamation);
        $responseRow->setContenu($contenu);
        $responseRow->setDateResponse($now);
        $responseRow->setAdmin($admin);

        $reclamation->setStatut('Traité');

        $em->persist($responseRow);
        $em->flush();

        $destinataire = $reclamation->getUser();
        $contenuHtml = nl2br(htmlspecialchars($contenu, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $fromEmail = (string) $this->getParameter('app.mailer_from_email');
        $fromName = (string) $this->getParameter('app.mailer_from_name');

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, $fromName))
            ->to($destinataire->getEmail())
            ->subject('Réponse à votre réclamation — Doura Mondo')
            ->htmlTemplate('emails/reclamation_reply.html.twig')
            ->context([
                'destinataire_prenom' => $destinataire->getPrenom(),
                'titre' => $reclamation->getTitre(),
                'contenu_html' => $contenuHtml,
            ]);

        try {
            $mailer->send($email);
            $this->addFlash('success', 'Réponse enregistrée, statut mis à « Traité » et e-mail envoyé au demandeur.');
        } catch (TransportExceptionInterface $e) {
            $detail = $e->getPrevious() instanceof \Throwable ? $e->getPrevious()->getMessage() : $e->getMessage();
            $this->addFlash('success', 'Réponse enregistrée et statut mis à « Traité ».');
            $this->addFlash(
                'warning',
                'Envoi du courriel impossible : '.$detail
            );
        }

        return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/modifier', name: 'app_admin_reclamations_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'La réclamation a été mise à jour.');

            return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_reclamations_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->getString('_token'))) {
            $em->remove($reclamation);
            $em->flush();
            $this->addFlash('success', 'La réclamation a été supprimée.');
        }

        return $this->redirectToRoute('app_admin_reclamations', [], Response::HTTP_SEE_OTHER);
    }
}
