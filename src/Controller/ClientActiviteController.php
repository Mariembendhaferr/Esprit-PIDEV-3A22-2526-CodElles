<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PexelsService;
use App\Service\OpenAIService;
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

    #[Route('/match', name: 'app_client_match', methods: ['GET'])]
    public function match(ActiviteRepository $repo): Response
    {
        $activites = $repo->createQueryBuilder('a')
            ->where('a.disponibiliteActivite = true')
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    
        // Get distinct locations for setup dropdown
        $locations = $repo->createQueryBuilder('a')
            ->select('DISTINCT a.localisationActivite as lieu')
            ->where('a.disponibiliteActivite = true')
            ->andWhere('a.localisationActivite IS NOT NULL')
            ->getQuery()
            ->getResult();
    
        // Serialize activites for JS
        $activitesData = array_map(fn($a) => [
            'id'                   => $a->getId(),
            'nomActivite'          => $a->getNomActivite(),
            'descriptionActivite'  => $a->getDescriptionActivite(),
            'categorieActivite'    => $a->getCategorieActivite(),
            'localisationActivite' => $a->getLocalisationActivite(),
            'coutActivite'         => $a->getCoutActivite(),
            'dureeActivite'        => $a->getDureeActivite(),
            'imageActivite'        => $a->getImageActivite(),
            'vibe_tags'            => [$a->getCategorieActivite(), $a->getLocalisationActivite()],
        ], $activites);
    
        return $this->render('clientActivite/match.html.twig', [
            'activites' => $activitesData,
            'locations' => array_column($locations, 'lieu'),
        ]);
    }
    
  #[Route('/match-ai', name: 'app_client_match_ai', methods: ['POST'])]
public function matchAI(Request $request, OpenAIService $ai): JsonResponse
{
    // Get the data from JavaScript
    $data = json_decode($request->getContent(), true);
 
    // Prepare the data array for the service
    $aiData = [
        'nom' => $data['nom'] ?? 'cette activité',
        'localisation' => $data['localisation'] ?? 'Tunisie',
        'categorie' => $data['categorie'] ?? 'Découverte',
        'prix' => $data['prix'] ?? '0',
        'description' => $data['description'] ?? ''
    ];
 
    try {
        // Call the service with the data array
        $message = $ai->generateMatchMessage($aiData);
 
        return new JsonResponse(['message' => $message]);
    } catch (\Exception $e) {
        // Fallback if AI fails
        error_log('Match AI Error: ' . $e->getMessage());
        return new JsonResponse([
            'message' => "Excellent choix ! Vous avez une vraie passion pour les expériences authentiques. Préparez-vous à vivre un moment inoubliable !"
        ]);
    }
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

    #[Route('/ai-message/{id}', name: 'app_client_ai_message', methods: ['GET'])]
    public function generateAIMessage(int $id, ActiviteRepository $repo, OpenAIService $openAI): JsonResponse
    {
        $activite = $repo->find($id);
        
        if (!$activite) {
            return new JsonResponse(['message' => 'Activité non trouvée'], 404);
        }

        $message = $openAI->generateMatchMessage(
            $activite->getNomActivite(),
            $activite->getCategorieActivite(),
            $activite->getLocalisationActivite() ?? 'Destination inconnue',
            (string)$activite->getDureeActivite(),
            (string)$activite->getCoutActivite()
        );

        return new JsonResponse(['message' => $message]);
    }
   
}
