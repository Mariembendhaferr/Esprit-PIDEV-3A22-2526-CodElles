<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\PexelsService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/activite')]
final class ActiviteController extends AbstractController
{
    // ── LIST ─────────────────────────────────────────────────
    #[Route('/', name: 'app_activite_index', methods: ['GET'])]
    public function index(Request $request, ActiviteRepository $repo): Response
    {
        $search    = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $sortPrix  = $request->query->get('sortPrix', '');
        $page      = max(1, (int)$request->query->get('page', 1));
        $itemsPerPage = 10;

        $qb = $repo->createQueryBuilder('a')
            ->leftJoin('a.fournisseurs', 'f')
            ->addSelect('f');

        if ($search) {
            $qb->andWhere('a.nomActivite LIKE :search OR a.localisationActivite LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($categorie) {
            $qb->andWhere('a.categorieActivite = :categorie')
               ->setParameter('categorie', $categorie);
        }
        if ($sortPrix === 'asc')       $qb->orderBy('a.coutActivite', 'ASC');
        elseif ($sortPrix === 'desc')  $qb->orderBy('a.coutActivite', 'DESC');
        else                           $qb->orderBy('a.nomActivite',  'ASC');

        $total      = count((clone $qb)->getQuery()->getResult());
        $totalPages = max(1, (int)ceil($total / $itemsPerPage));
        $page       = min($page, $totalPages);

        $activites = $qb->setFirstResult(($page - 1) * $itemsPerPage)
                        ->setMaxResults($itemsPerPage)
                        ->getQuery()->getResult();

        return $this->render('activite/index.html.twig', [
            'activites'   => $activites,
            'search'      => $search,
            'categorie'   => $categorie,
            'sortPrix'    => $sortPrix,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'total'       => $total,
        ]);
    }

    // ── CREATE ────────────────────────────────────────────────
    #[Route('/new', name: 'app_activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $activite = new Activite();
        $activite->setStatutActivite('en_attente');

        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($activite);
            $em->flush();

            $this->notifierFournisseurs($activite, $mailer, $em);

            $this->addFlash('success', 'Activité ajoutée ! Les fournisseurs ont été notifiés par email.');
            return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/new.html.twig', [
            'activite' => $activite,
            'form'     => $form,
        ]);
    }

    // ── EXPORT PDF ────────────────────────────────────────────
    #[Route('/export-pdf', name: 'app_activite_export_pdf', methods: ['GET'])]
    public function exportPdf(ActiviteRepository $repo): Response
    {
        $activites = $repo->findAll();
        $html = $this->renderView('activite/activites_export.html.twig', [
            'activites' => $activites,
            'generatedAt'      => new \DateTime(),
            'filters'     => [
            'search'    => '',
            'categorie' => '',
            'sortPrix'  => '',
        ],
        ]);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="activites_' . date('Y-m-d') . '.pdf"',
        ]);
    }

    // ── FOURNISSEUR RESPONSE ROUTES ───────────────────────────
    #[Route('/reponse/{token}/accepter', name: 'app_activite_accepter', methods: ['GET'])]
    public function accepter(string $token, ActiviteRepository $repo, EntityManagerInterface $em): Response
    {
        $activite = $repo->findOneBy(['assignationToken' => $token]);

        if (!$activite) {
            return $this->render('activite/reponse.html.twig', [
                'statut'  => 'erreur',
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        $activite->setStatutActivite('acceptee');
        $activite->setAssignationToken(null);
        $em->flush();

        return $this->render('activite/reponse.html.twig', [
            'statut'   => 'acceptee',
            'activite' => $activite,
            'message'  => 'Vous avez accepté l\'activité "' . $activite->getNomActivite() . '".',
        ]);
    }

    //stats
    #[Route('/stats', name: 'app_activite_stats', methods: ['GET'])]
    public function stats(
        ActiviteRepository $repo,
        \App\Repository\FournisseurActiviteRepository $fRepo
    ): Response {
        $all = $repo->findAll();
    
        $total       = count($all);
        $disponibles = count(array_filter($all, fn($a) => $a->isDisponibiliteActivite()));
        $enAttente   = count(array_filter($all, fn($a) => $a->getStatutActivite() === 'en_attente'));
        $acceptees   = count(array_filter($all, fn($a) => $a->getStatutActivite() === 'acceptee'));
        $refusees    = count(array_filter($all, fn($a) => $a->getStatutActivite() === 'refusee'));
    
        $prixTotal = 0;
        $parCategorie = [];
        foreach ($all as $a) {
            $prixTotal += $a->getCoutActivite();
            $cat = $a->getCategorieActivite() ?? 'Autre';
            $parCategorie[$cat] = ($parCategorie[$cat] ?? 0) + 1;
        }
    
        
        $sorted = $all;
        usort($sorted, fn($a, $b) => $b->getCoutActivite() <=> $a->getCoutActivite());
        $topActivites = array_slice($sorted, 0, 5);
    
        return $this->render('activite/stats.html.twig', [
            'stats' => [
                'total'            => $total,
                'disponibles'      => $disponibles,
                'prixMoyen'        => $total > 0 ? $prixTotal / $total : 0,
                'totalFournisseurs'=> count($fRepo->findAll()),
                'enAttente'        => $enAttente,
                'acceptees'        => $acceptees,
                'refusees'         => $refusees,
                'parCategorie'     => $parCategorie,
                'topActivites'     => $topActivites,
            ],
            'catLabels' => array_keys($parCategorie),   // ← ADD
            'catVals'   => array_values($parCategorie), // ← ADD
        ]);
    }

    #[Route('/pexels-search', name: 'app_activite_pexels', methods: ['GET'])]
    public function pexelsSearch(Request $request, PexelsService $pexels): JsonResponse
    {
        $query = $request->query->get('q', '');
        if (!$query) {
            return new JsonResponse(['error' => 'No query'], 400);
        }

        $url = $pexels->searchImage($query);

        return new JsonResponse(['url' => $url]);
    }

    #[Route('/favorites', name: 'app_client_favorites', methods: ['GET'])]
    public function favorites(): Response
    {
        return $this->render('client/favorites.html.twig');
    }

    #[Route('/reponse/{token}/refuser', name: 'app_activite_refuser', methods: ['GET'])]
    public function refuser(string $token, ActiviteRepository $repo, EntityManagerInterface $em): Response
    {
        $activite = $repo->findOneBy(['assignationToken' => $token]);

        if (!$activite) {
            return $this->render('activite/reponse.html.twig', [
                'statut'  => 'erreur',
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        $activite->setStatutActivite('refusee');
        $activite->setAssignationToken(null);
        $em->flush();

        return $this->render('activite/reponse.html.twig', [
            'statut'   => 'refusee',
            'activite' => $activite,
            'message'  => 'Vous avez refusé l\'activité "' . $activite->getNomActivite() . '".',
        ]);
    }

    // ── SHOW ──────────────────────────────────────────────────
    #[Route('/{id}', name: 'app_client_activity_show', methods: ['GET'])]
    public function show(Activite $activite, ActiviteRepository $repo): Response
    {
        if (!$activite->isDisponibiliteActivite()) {
            throw $this->createNotFoundException('Cette activité n\'est pas disponible');
        }

        $related = $repo->createQueryBuilder('a')
            ->where('a.categorieActivite = :cat')
            ->andWhere('a.id != :id')
            //->setParameter('cat', strtolower($activite->getCategorieActivite()))
            ->setParameter('id', $activite->getId())
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('client/show.html.twig', [
            'activite'         => $activite,
            'relatedActivites' => $related,
        ]);
    }
    // ── EDIT ──────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'app_activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activite $activite, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $oldFournisseurs = $activite->getFournisseurs()->toArray();

        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            // Notify only newly added fournisseurs
            $newFournisseurs = array_filter(
                $activite->getFournisseurs()->toArray(),
                fn($f) => !in_array($f, $oldFournisseurs)
            );

            if (!empty($newFournisseurs)) {
                $this->notifierFournisseurs($activite, $mailer, $em, $newFournisseurs);
                $this->addFlash('success', 'Activité modifiée ! Les nouveaux fournisseurs ont été notifiés.');
            } else {
                $this->addFlash('success', 'Activité modifiée avec succès !');
            }

            return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/edit.html.twig', [
            'activite' => $activite,
            'form'     => $form,
        ]);
    }

    // ── DELETE ────────────────────────────────────────────────
    #[Route('/{id}', name: 'app_activite_delete', methods: ['POST'])]
    public function delete(Request $request, Activite $activite, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activite->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($activite);
            $em->flush();
            $this->addFlash('success', 'Activité supprimée !');
        }
        return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
    }



    // ── PRIVATE HELPER ────────────────────────────────────────
    private function notifierFournisseurs(
        Activite $activite,
        MailerInterface $mailer,
        EntityManagerInterface $em,
        array $fournisseurs = []
    ): void {
        $targets = empty($fournisseurs) ? $activite->getFournisseurs()->toArray() : $fournisseurs;
        if (empty($targets)) return;

        // Generate unique token and save it
        $token = bin2hex(random_bytes(32));
        $activite->setAssignationToken($token);
        $em->persist($activite);
        $em->flush();

        $urlAccepter = $this->generateUrl(
            'app_activite_accepter',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $urlRefuser = $this->generateUrl(
            'app_activite_refuser',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        foreach ($targets as $fournisseur) {
            $html = $this->renderView('emails/assignation.html.twig', [
                'fournisseur' => $fournisseur,
                'activite'    => $activite,
                'urlAccepter' => $urlAccepter,
                'urlRefuser'  => $urlRefuser,
            ]);

            $email = (new Email())
                ->from('mariem.bendhafer394@gmail.com')
                ->to($fournisseur->getEmailFournisseur())
                ->subject('🌍 Doura Mondo — Nouvelle activité assignée : ' . $activite->getNomActivite())
                ->html($html);

            $mailer->send($email);
        }
    }

    
}