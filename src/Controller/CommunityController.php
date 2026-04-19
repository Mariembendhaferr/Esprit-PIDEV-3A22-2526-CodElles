<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CommunityController extends AbstractController
{
    #[Route('/community', name: 'community_index')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        SessionInterface $session
    ): Response {
        $search      = $request->query->get('search', '');
        $filter      = $request->query->get('filter', 'Tous les membres');
        $isAjax      = $request->isXmlHttpRequest(); // Check if AJAX request

        $currentUserId = $session->get('user_id');

        // Get all users EXCEPT current user
        $allUsers = $userRepository->findAll();
        if ($currentUserId) {
            $allUsers = array_filter($allUsers, fn($user) => $user->getIdUser() !== $currentUserId);
        }

        // Apply search
        if (!empty($search)) {
            $lower = strtolower(trim($search));
            $allUsers = array_filter($allUsers, function($user) use ($lower) {
                return str_contains(strtolower($user->getNom() ?? ''), $lower)
                    || str_contains(strtolower($user->getPrenom() ?? ''), $lower)
                    || str_contains(strtolower($user->getUsername() ?? ''), $lower)
                    || str_contains(strtolower($user->getEmail() ?? ''), $lower);
            });
        }

        // Apply filter
        $now = new \DateTime();
        $startOfWeek = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
        $endOfWeek   = (clone $startOfWeek)->modify('+7 days');
        $startOfMonth = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth   = (clone $now)->modify('last day of this month')->setTime(23, 59, 59);

        $allUsers = array_filter($allUsers, function($user) use ($filter, $startOfWeek, $endOfWeek, $startOfMonth, $endOfMonth) {
            return match($filter) {
                'Actifs uniquement'     => $user->getStatut() === 'actif',
                'Inactifs uniquement'   => $user->getStatut() === 'inactif',
                'Voyageurs'             => $user->getRole() === 'voyageur',
                'Administrateurs'       => $user->getRole() === 'admin',
                'Inscrits ce mois'      => $user->getDateInscription() >= $startOfMonth && $user->getDateInscription() <= $endOfMonth,
                'Inscrits cette semaine'=> $user->getDateInscription() >= $startOfWeek && $user->getDateInscription() < $endOfWeek,
                'Les mieux notés'       => $user->getAverageRating() > 0,
                default                 => true,
            };
        });

        // Sort best rated
        if ($filter === 'Les mieux notés') {
            usort($allUsers, fn($a, $b) => $b->getAverageRating() <=> $a->getAverageRating());
        }

        $allUsers = array_values($allUsers);

        // If AJAX request, return only the user list HTML fragment
        if ($isAjax) {
            return $this->render('community/_user_list.html.twig', [
                'users' => $allUsers,
            ]);
        }

        // Normal request: render full page
        return $this->render('community/index.html.twig', [
            'users'          => $allUsers,
            'search'         => $search,
            'filter'         => $filter,
            'totalCount'     => count($allUsers),
            'activeCount'    => count(array_filter($allUsers, fn($u) => $u->getStatut() === 'actif')),
            'filters'        => [
                'Tous les membres', 'Actifs uniquement', 'Inactifs uniquement',
                'Voyageurs', 'Administrateurs', 'Inscrits ce mois',
                'Inscrits cette semaine', 'Les mieux notés',
            ],
            'currentUserId'  => $currentUserId,
        ]);
    }
}