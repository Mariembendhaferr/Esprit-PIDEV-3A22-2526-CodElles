<?php

namespace App\Controller\Admin;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reclamations')]
class ReclamationController extends AbstractController
{
    #[Route('', name: 'app_admin_reclamations', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $q = $request->query->getString('q', '');
        $statut = $request->query->getString('statut', '');
        $priorite = $request->query->getString('priorite', '');

        $reclamations = $reclamationRepository->searchAndFilter(
            '' !== $q ? $q : null,
            '' !== $statut ? $statut : null,
            '' !== $priorite ? $priorite : null,
        );

        $vars = [
            'reclamations' => $reclamations,
            'filter_q' => $q,
            'filter_statut' => $statut,
            'filter_priorite' => $priorite,
        ];

        if ($request->query->getBoolean('partial')) {
            return $this->render('admin/reclamation/_table_rows.html.twig', $vars);
        }

        return $this->render('admin/reclamation/index.html.twig', $vars);
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
