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

#[Route('/activite')]
final class ActiviteController extends AbstractController
{
#[Route('/', name: 'app_activite_index', methods: ['GET'])]
public function index(Request $request, ActiviteRepository $repo): Response
{
    $search    = $request->query->get('search', '');
    $categorie = $request->query->get('categorie', '');
    $sortPrix  = $request->query->get('sortPrix', '');
    $page      = max(1, (int)$request->query->get('page', 1));
    $itemsPerPage = 10;

    $qb = $repo->createQueryBuilder('a');

    if ($search) {
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('a.nomActivite',        ':search'),
                $qb->expr()->like('a.descriptionActivite',':search'),
                $qb->expr()->like('a.localisationActivite',':search'),
            )
        )->setParameter('search', '%' . $search . '%');
    }

    if ($categorie) {
        $qb->andWhere('a.categorieActivite = :categorie')
           ->setParameter('categorie', $categorie);
    }

    if ($sortPrix === 'asc') {
        $qb->orderBy('a.coutActivite', 'ASC');
    } elseif ($sortPrix === 'desc') {
        $qb->orderBy('a.coutActivite', 'DESC');
    } else {
        $qb->orderBy('a.nomActivite', 'ASC');
    }

    // Get total count
    $countQb = clone $qb;
    $total = count($countQb->getQuery()->getResult());
    $totalPages = ceil($total / $itemsPerPage);
    $page = min($page, max(1, $totalPages));

    // Apply pagination
    $activites = $qb->setFirstResult(($page - 1) * $itemsPerPage)
                     ->setMaxResults($itemsPerPage)
                     ->getQuery()
                     ->getResult();

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

    #[Route('/new', name: 'app_activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activite = new Activite();
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activite);
            $entityManager->flush();

            return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/new.html.twig', [
            'activite' => $activite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activite_show', methods: ['GET'])]
    public function show(Activite $activite): Response
    {
        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activite $activite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/edit.html.twig', [
            'activite' => $activite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activite_delete', methods: ['POST'])]
    public function delete(Request $request, Activite $activite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$activite->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($activite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activite_index', [], Response::HTTP_SEE_OTHER);
    }
}
