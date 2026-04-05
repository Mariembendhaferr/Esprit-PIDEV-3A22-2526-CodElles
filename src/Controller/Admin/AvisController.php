<?php

namespace App\Controller\Admin;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Repository\ActiviteRepository;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/avis')]
class AvisController extends AbstractController
{
    #[Route('', name: 'app_admin_avis', methods: ['GET'])]
    public function index(Request $request, AvisRepository $avisRepository, ActiviteRepository $activiteRepository): Response
    {
        $q = $request->query->getString('q', '');
        $note = $request->query->getString('note', '');
        $activiteId = $request->query->getInt('activite', 0);

        $avis_list = $avisRepository->searchAndFilter(
            '' !== $q ? $q : null,
            '' !== $note ? $note : null,
            $activiteId > 0 ? $activiteId : null,
        );

        $vars = [
            'avis_list' => $avis_list,
            'filter_q' => $q,
            'filter_note' => $note,
            'filter_activite' => $activiteId,
            'activites' => $activiteRepository->findAllOrderedByNom(),
        ];

        if ($request->query->getBoolean('partial')) {
            return $this->render('admin/avis/_table_rows.html.twig', $vars);
        }

        return $this->render('admin/avis/index.html.twig', $vars);
    }

    #[Route('/nouveau', name: 'app_admin_avis_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $avis = new Avis();
        $avis->setDateAvis(new \DateTime());

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $avis->getDateAvis()) {
                $avis->setDateAvis(new \DateTime());
            }
            $em->persist($avis);
            $em->flush();
            $this->addFlash('success', 'L’avis a été enregistré.');

            return $this->redirectToRoute('app_admin_avis', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/avis/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_avis_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Avis $avis, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'L’avis a été mis à jour.');

            return $this->redirectToRoute('app_admin_avis', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/avis/edit.html.twig', [
            'avis' => $avis,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_avis_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Avis $avis, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avis->getId(), $request->request->getString('_token'))) {
            $em->remove($avis);
            $em->flush();
            $this->addFlash('success', 'L’avis a été supprimé.');
        }

        return $this->redirectToRoute('app_admin_avis', [], Response::HTTP_SEE_OTHER);
    }
}
