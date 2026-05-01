<?php
// src/Controller/NotificationController.php

namespace App\Controller;

use App\Repository\CommunityPostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    // ── Page notifications admin ──────────────────────────────────────────────

    #[Route('/admin/notifications', name: 'app_admin_notifications')]
    public function index(CommunityPostRepository $repo): Response
    {
        // Tous les posts triés par date de soumission desc
        $allPosts = $repo->findBy([], ['submittedAt' => 'DESC']);

        // Posts en attente (pending) = nouvelles notifications
        $pendingPosts = $repo->findBy(['moderationStatus' => 'pending'], ['submittedAt' => 'DESC']);

        // Posts approuvés = galerie communauté
        $approvedPosts = $repo->findBy(['moderationStatus' => 'approved'], ['approvedAt' => 'DESC']);

        // Posts rejetés
        $rejectedPosts = $repo->findBy(['moderationStatus' => 'rejected'], ['submittedAt' => 'DESC']);

        // Top 3 destinations les plus partagées (parmi tous les posts)
        $topDestinations = $repo->findTopDestinations(3);

        return $this->render('admin/notifications.html.twig', [
            'allPosts'       => $allPosts,
            'pendingPosts'   => $pendingPosts,
            'approvedPosts'  => $approvedPosts,
            'rejectedPosts'  => $rejectedPosts,
            'topDestinations'=> $topDestinations,
            'totalPosts'     => count($allPosts),
            'nbPending'      => count($pendingPosts),
        ]);
    }

    // ── Endpoint AJAX : nombre de notifications non lues (badge sonnette) ────

    #[Route('/admin/notifications/count', name: 'app_admin_notifications_count', methods: ['GET'])]
    public function count(CommunityPostRepository $repo): JsonResponse
    {
        $nb = $repo->count(['moderationStatus' => 'pending']);
        return $this->json(['count' => $nb]);
    }
}
