<?php
// src/Controller/PublicProfileController.php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicProfileController extends AbstractController
{
    #[Route('/p/{username}', name: 'public_profile', methods: ['GET'])]
    public function show(string $username, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        
        if (!$user || $user->getStatut() !== 'actif') {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        return $this->render('public_profile/show.html.twig', [
            'user' => $user,
        ]);
    }
}