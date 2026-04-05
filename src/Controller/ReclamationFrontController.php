<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationPublicType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReclamationFrontController extends AbstractController
{
    #[Route('/reclamations', name: 'app_reclamations', methods: ['GET', 'POST'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository, EntityManagerInterface $em): Response
    {
        $reclamation = new Reclamation();
        $reclamation->setDateCreation(new \DateTime());
        $reclamation->setStatut('En attente');
        $reclamation->setPriorite('Moyenne');

        $form = $this->createForm(ReclamationPublicType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $reclamation->getDateCreation()) {
                $reclamation->setDateCreation(new \DateTime());
            }
            $em->persist($reclamation);
            $em->flush();
            $this->addFlash('success', 'Votre réclamation a bien été enregistrée. Notre équipe la traitera dans les meilleurs délais.');

            return $this->redirectToRoute('app_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamationRepository->findAllWithUser(),
            'form' => $form,
            'open_form_modal' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }
}
