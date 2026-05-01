<?php

namespace App\Controller;

use App\Entity\Favori;
use App\Entity\User;
use App\Entity\Voyage;
use App\Repository\FavoriRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FavoriController extends AbstractController
{
    // ✅ Fix: return type explicitement User|null (plus object|null)
    private function getUserFromSession(Request $request, UserRepository $userRepository): ?User
    {
        $session = $request->getSession();
        $userId  = $session->get('user_id');

        if (!$userId) {
            return null;
        }

        // ✅ Fix: find() retourne object|null — on caste proprement
        $user = $userRepository->find($userId);
        return $user instanceof User ? $user : null;
    }

    #[Route('/mes-favoris', name: 'app_mes_favoris')]
    public function mesFavoris(Request $request, FavoriRepository $favoriRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUserFromSession($request, $userRepository);

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $favoris = $favoriRepository->findFavorisByUser($user);

        return $this->render('voyage/mes_favoris.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    #[Route('/favori/ajouter/{id}', name: 'app_favori_ajouter', methods: ['POST'])]
    public function ajouterFavori(Request $request, Voyage $voyage, EntityManagerInterface $em, FavoriRepository $favoriRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUserFromSession($request, $userRepository);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        // ✅ Fix: getIdVoyage() peut retourner null — on s'assure que c'est un int
        $voyageId = $voyage->getIdVoyage();
        if ($voyageId === null) {
            return $this->json(['success' => false, 'message' => 'Voyage invalide'], 400);
        }

        if ($favoriRepository->isFavori($user, $voyageId)) {
            return $this->json(['success' => false, 'message' => 'Déjà dans vos favoris'], 400);
        }

        $favori = new Favori();
        $favori->setUser($user);
        $favori->setVoyage($voyage);
        $favori->setDateAjout(new \DateTime());

        $em->persist($favori);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Voyage ajouté aux favoris']);
    }

    #[Route('/favori/supprimer/{id}', name: 'app_favori_supprimer', methods: ['DELETE'])]
    public function supprimerFavori(Request $request, Voyage $voyage, EntityManagerInterface $em, FavoriRepository $favoriRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUserFromSession($request, $userRepository);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non connecté'], 401);
        }

        // ✅ Fix: getIdVoyage() peut retourner null
        $voyageId = $voyage->getIdVoyage();
        if ($voyageId === null) {
            return $this->json(['success' => false, 'message' => 'Voyage invalide'], 400);
        }

        $favori = $favoriRepository->findFavoriByUserAndVoyage($user, $voyageId);

        if (!$favori) {
            return $this->json(['success' => false, 'message' => 'Favori non trouvé'], 404);
        }

        $em->remove($favori);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Voyage retiré des favoris']);
    }
}