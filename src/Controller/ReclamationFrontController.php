<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationPublicType;
use App\Repository\ReclamationRepository;
use App\Repository\ReclamationResponseRepository;
use App\Service\SightengineModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReclamationFrontController extends AbstractController
{
    #[Route('/reclamations', name: 'app_reclamations', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ReclamationRepository $reclamationRepository,
        ReclamationResponseRepository $reclamationResponseRepository,
        EntityManagerInterface $em,
        SightengineModerationService $moderation,
    ): Response
    {
        $reclamation = new Reclamation();
        $reclamation->setDateCreation(new \DateTime());
        $reclamation->setStatut('En attente');
        $reclamation->setPriorite('Moyenne');

        $form = $this->createForm(ReclamationPublicType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $titre = (string) $reclamation->getTitre();
            $description = (string) $reclamation->getDescription();
            $titreBlocked = $moderation->textHasIssues($titre);
            $descriptionBlocked = $moderation->textHasIssues($description);
            if ($titreBlocked || $descriptionBlocked) {
                $this->addFlash(
                    'error',
                    'L’objet ou la description contient des termes inappropriés. Modifiez le texte pour qu’il soit acceptable, puis réessayez.'
                );
                if ($titreBlocked) {
                    $form->get('titre')->addError(new FormError('Texte non accepté (modération automatique).'));
                }
                if ($descriptionBlocked) {
                    $form->get('description')->addError(new FormError('Texte non accepté (modération automatique).'));
                }

                $reclamations = $reclamationRepository->findAllWithUser();

                return $this->render('reclamation/index.html.twig', [
                    'reclamations' => $reclamations,
                    'responses_by_reclamation' => $reclamationResponseRepository->findGroupedByReclamations($reclamations),
                    'form' => $form,
                    'open_form_modal' => true,
                ]);
            }

            if (null === $reclamation->getDateCreation()) {
                $reclamation->setDateCreation(new \DateTime());
            }
            $em->persist($reclamation);
            $em->flush();
            $this->addFlash('success', 'Votre réclamation a bien été enregistrée. Notre équipe la traitera dans les meilleurs délais.');

            return $this->redirectToRoute('app_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        $reclamations = $reclamationRepository->findAllWithUser();

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'responses_by_reclamation' => $reclamationResponseRepository->findGroupedByReclamations($reclamations),
            'form' => $form,
            'open_form_modal' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }
}
