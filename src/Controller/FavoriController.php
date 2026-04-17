<?php
// src/Controller/FavoriController.php

namespace App\Controller;

use App\Entity\Favori;
use App\Entity\Voyage;
use App\Repository\FavoriRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FavoriController extends AbstractController
{
    private const STATIC_USER_ID = 36;

    #[Route('/mes-favoris', name: 'app_mes_favoris')]
    public function mesFavoris(FavoriRepository $favoriRepository, UserRepository $userRepository): Response
    {
        $user = $userRepository->find(self::STATIC_USER_ID);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        $favoris = $favoriRepository->findFavorisByUser($user);
        
        return $this->render('voyage/mes_favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    #[Route('/favori/ajouter/{id}', name: 'app_favori_ajouter', methods: ['POST'])]
    public function ajouterFavori(Voyage $voyage, EntityManagerInterface $em, FavoriRepository $favoriRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find(self::STATIC_USER_ID);
        
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }
        
        if ($favoriRepository->isFavori($user, $voyage->getIdVoyage())) {
            return $this->json(['success' => false, 'message' => 'Déjà dans vos favoris'], 400);
        }

        $favori = new Favori();
        $favori->setUser($user);
        $favori->setVoyage($voyage);
        $favori->setDateAjout(new \DateTime());

        $em->persist($favori);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Voyage ajouté aux favoris'
        ]);
    }

    #[Route('/favori/supprimer/{id}', name: 'app_favori_supprimer', methods: ['DELETE'])]
    public function supprimerFavori(Voyage $voyage, EntityManagerInterface $em, FavoriRepository $favoriRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find(self::STATIC_USER_ID);
        
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }
        
        $favori = $favoriRepository->findFavoriByUserAndVoyage($user, $voyage->getIdVoyage());
        
        if (!$favori) {
            return $this->json(['success' => false, 'message' => 'Favori non trouvé'], 404);
        }

        $em->remove($favori);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Voyage retiré des favoris'
        ]);
    }
}