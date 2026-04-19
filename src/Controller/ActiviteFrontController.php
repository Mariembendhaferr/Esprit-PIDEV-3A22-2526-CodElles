<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Entity\Avis;
use App\Form\AvisPublicType;
use App\Repository\ActiviteRepository;
use App\Repository\AvisRepository;
use App\Service\SightengineModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ActiviteFrontController extends AbstractController
{
    public const SESSION_VISITOR_USER_ID = 'front_visitor_user_id';

    #[Route('/activites', name: 'app_activites', methods: ['GET'])]
    public function index(ActiviteRepository $activiteRepository): Response
    {
        return $this->render('activite/index.html.twig', [
            'activites' => $activiteRepository->findAllOrderedByNom(),
        ]);
    }

    #[Route('/activites/{activiteId}/avis/{avisId}/modifier', name: 'app_activite_avis_edit', requirements: ['activiteId' => '\d+', 'avisId' => '\d+'], methods: ['GET', 'POST'])]
    public function editAvis(
        Request $request,
        int $activiteId,
        int $avisId,
        ActiviteRepository $activiteRepository,
        AvisRepository $avisRepository,
        EntityManagerInterface $em,
        SightengineModerationService $moderation,
    ): Response {
        $activite = $activiteRepository->find($activiteId);
        $avis = $avisRepository->find($avisId);
        if (!$activite instanceof Activite || !$avis instanceof Avis) {
            throw $this->createNotFoundException();
        }
        if ($avis->getActivite()->getId() !== $activite->getId()) {
            throw $this->createNotFoundException();
        }
        if (!$this->canVisitorManageAvis($request, $avis)) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres avis.');
        }

        $form = $this->createForm(AvisPublicType::class, $avis, ['edit_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire = (string) $avis->getCommentaire();
            if ($moderation->textHasIssues($commentaire)) {
                $this->addFlash(
                    'error',
                    'Votre commentaire contient des termes inappropriés. Modifiez-le pour retirer le langage grossier ou offensant.'
                );
                $form->get('commentaire')->addError(new FormError(
                    'Texte non accepté : modération automatique (langage inapproprié).'
                ));

                return $this->render('activite/avis_edit.html.twig', [
                    'activite' => $activite,
                    'avis' => $avis,
                    'form' => $form,
                ]);
            }

            $em->flush();
            $this->addFlash('success', 'Votre avis a bien été mis à jour.');

            return $this->redirectToRoute('app_activite_show', ['id' => $activite->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/avis_edit.html.twig', [
            'activite' => $activite,
            'avis' => $avis,
            'form' => $form,
        ]);
    }

    #[Route('/activites/{activiteId}/avis/{avisId}/supprimer', name: 'app_activite_avis_delete', requirements: ['activiteId' => '\d+', 'avisId' => '\d+'], methods: ['POST'])]
    public function deleteAvis(
        Request $request,
        int $activiteId,
        int $avisId,
        ActiviteRepository $activiteRepository,
        AvisRepository $avisRepository,
        EntityManagerInterface $em,
    ): Response {
        $activite = $activiteRepository->find($activiteId);
        $avis = $avisRepository->find($avisId);
        if (!$activite instanceof Activite || !$avis instanceof Avis) {
            throw $this->createNotFoundException();
        }
        if ($avis->getActivite()->getId() !== $activite->getId()) {
            throw $this->createNotFoundException();
        }
        if (!$this->canVisitorManageAvis($request, $avis)) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres avis.');
        }

        if (!$this->isCsrfTokenValid('delete_avis_'.$avis->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $em->remove($avis);
        $em->flush();
        $this->addFlash('success', 'Votre avis a bien été supprimé.');

        return $this->redirectToRoute('app_activite_show', ['id' => $activite->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/activites/{id}', name: 'app_activite_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        Activite $activite,
        AvisRepository $avisRepository,
        EntityManagerInterface $em,
        SightengineModerationService $moderation,
    ): Response {
        $avis = new Avis();
        $avis->setActivite($activite);
        $avis->setDateAvis(new \DateTime());

        $form = $this->createForm(AvisPublicType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire = (string) $avis->getCommentaire();
            if ($moderation->textHasIssues($commentaire)) {
                $this->addFlash(
                    'error',
                    'Votre commentaire contient des termes inappropriés. Modifiez le texte pour un ton adapté au site public, puis réessayez.'
                );
                $form->get('commentaire')->addError(new FormError(
                    'Texte non accepté : modération automatique (langage inapproprié).'
                ));

                return $this->render('activite/show.html.twig', [
                    'activite' => $activite,
                    'avis_list' => $avisRepository->findByActiviteOrdered($activite),
                    'form' => $form,
                    'visitor_user_id' => $request->getSession()->get(self::SESSION_VISITOR_USER_ID),
                ]);
            }

            if (null === $avis->getDateAvis()) {
                $avis->setDateAvis(new \DateTime());
            }
            $em->persist($avis);
            $em->flush();
            $request->getSession()->set(self::SESSION_VISITOR_USER_ID, $avis->getUser()->getId());
            $this->addFlash('success', 'Merci ! Votre avis a bien été enregistré.');

            return $this->redirectToRoute('app_activite_show', ['id' => $activite->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
            'avis_list' => $avisRepository->findByActiviteOrdered($activite),
            'form' => $form,
            'visitor_user_id' => $request->getSession()->get(self::SESSION_VISITOR_USER_ID),
        ]);
    }

    private function canVisitorManageAvis(Request $request, Avis $avis): bool
    {
        $sessionUserId = $request->getSession()->get(self::SESSION_VISITOR_USER_ID);

        return null !== $sessionUserId && (int) $sessionUserId === $avis->getUser()->getId();
    }
}
