<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activities')]
final class ClientActiviteController extends AbstractController
{
    #[Route('/', name: 'app_client_activities', methods: ['GET'])]
    public function index(Request $request, ActiviteRepository $repo): Response
    {
        $search    = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $sortPrix  = $request->query->get('sortPrix', '');
        $page      = max(1, (int)$request->query->get('page', 1));
        $itemsPerPage = 12;

        $qb = $repo->createQueryBuilder('a')
            ->where('a.disponibiliteActivite = :available')
            ->setParameter('available', true);

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

        return $this->render('client/index.html.twig', [
            'activites'   => $activites,
            'search'      => $search,
            'categorie'   => $categorie,
            'sortPrix'    => $sortPrix,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'total'       => $total,
        ]);
    }

    #[Route('/{id}', name: 'app_client_activity_show', methods: ['GET'])]
    public function show(Activite $activite): Response
    {
        // Only show available activities to clients
        if (!$activite->isDisponibiliteActivite()) {
            throw $this->createNotFoundException('Cette activité n\'est pas disponible');
        }

        return $this->render('client/show.html.twig', [
            'activite' => $activite,
        ]);
    }
}
