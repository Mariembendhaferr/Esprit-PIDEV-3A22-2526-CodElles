<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PexelsService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/activities')]
final class ClientActiviteController extends AbstractController
{
    #[Route('/', name: 'app_client_activities', methods: ['GET'])]
    
    #[Route('/explore', name: 'app_client_explore', methods: ['GET'])]
    public function explore(ActiviteRepository $repo): Response
    {
        $activites = $repo->createQueryBuilder('a')
            ->where('a.disponibiliteActivite = true')
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('clientActivite/explore.html.twig', [
            'activites' => $activites,
        ]);
    }

    #[Route('/destinations', name: 'app_client_destinations', methods: ['GET'])]
    public function destinations(ActiviteRepository $repo, PexelsService $pexels): JsonResponse
    {
        $locations = $repo->createQueryBuilder('a')
            ->select('a.localisationActivite as lieu, COUNT(a.id) as nbActivites')
            ->where('a.disponibiliteActivite = true')
            ->andWhere('a.localisationActivite IS NOT NULL')
            ->groupBy('a.localisationActivite')
            ->orderBy('nbActivites', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($locations as $loc) {
            $image = $pexels->searchImage($loc['lieu'] . ' travel destination');
            $result[] = [
                'lieu'        => $loc['lieu'],
                'nbActivites' => $loc['nbActivites'],
                'image'       => $image,
            ];
        }

        return new JsonResponse($result);
    }



    #[Route('/{id}', name: 'app_client_activity_show', methods: ['GET'])]
    public function show(int $id, ActiviteRepository $repo): Response
    {
        // Eager load everything in ONE query
        $activite = $repo->createQueryBuilder('a')
            ->leftJoin('a.fournisseurs', 'f')
            ->addSelect('f')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$activite || !$activite->isDisponibiliteActivite()) {
            throw $this->createNotFoundException('Activité non disponible');
        }

        $related = $repo->findRelatedActivities($activite, 3);

        return $this->render('clientActivite/show.html.twig', [
            'activite'         => $activite,
            'relatedActivites' => $related,
        ]);
    }
   
}
