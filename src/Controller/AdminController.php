<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    private function requireAdmin(SessionInterface $session): bool
    {
        return $session->get('user_role') === 'admin';
    }

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        if (!$this->requireAdmin($session)) {
            return $this->redirectToRoute('login');
        }

        $userId = $session->get('user_id');
        $user   = $userRepository->find($userId);

        return $this->render('admin/dashboard.html.twig', [
            'user'        => $user,
            'totalUsers'  => $userRepository->countTotalUsers(),
            'activeUsers' => $userRepository->countActiveUsers(),
        ]);
    }

 
}
