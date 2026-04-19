<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/statistics')]
class AdminStatisticsController extends AbstractController
{
    #[Route('/', name: 'admin_statistics')]
    public function index(UserRepository $userRepository): Response
    {
        $stats = [
            'total'     => $userRepository->countTotalUsers(),
            'active'    => $userRepository->countActiveUsers(),
            'inactive'  => $userRepository->countInactiveUsers(),
            'admins'    => $userRepository->countByRole('admin'),
            'voyageurs' => $userRepository->countByRole('voyageur'),
            'monthly'   => count($userRepository->getMonthlyNewUsers()),
            'weekly'    => count($userRepository->getWeeklyNewUsers()),
        ];

        $registrations = $userRepository->getRegistrationsLast7Days();

        return $this->render('admin/statistics.html.twig', [
            'stats'         => $stats,
            'registrations' => $registrations,
        ]);
    }

    #[Route('/export/{type}', name: 'admin_statistics_export')]
    public function export(string $type, UserRepository $userRepository): StreamedResponse
    {
        $now = new \DateTime();

        $fileNames = [
            'active'    => 'utilisateurs_actifs',
            'inactive'  => 'utilisateurs_inactifs',
            'admin'     => 'administrateurs',
            'voyageur'  => 'voyageurs',
            'monthly'   => 'nouveaux_mois_' . $now->format('m_Y'),
            'weekly'    => 'nouveaux_semaine_' . $now->format('d_m'),
        ];

        $users = match($type) {
            'active'   => $userRepository->findBy(['statut' => 'actif']),
            'inactive' => $userRepository->findBy(['statut' => 'inactif']),
            'admin'    => $userRepository->findBy(['role' => 'admin']),
            'voyageur' => $userRepository->findBy(['role' => 'voyageur']),
            'monthly'  => $userRepository->getMonthlyNewUsers(),
            'weekly'   => $userRepository->getWeeklyNewUsers(),
            default    => [],
        };

        $fileName = ($fileNames[$type] ?? 'export') . '.csv';

        $response = new StreamedResponse(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['ID', 'Nom', 'Prénom', 'Username', 'Email', 'Téléphone', 'Rôle', 'Statut', 'Date inscription']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->getIdUser(),
                    $user->getNom(),
                    $user->getPrenom(),
                    $user->getUsername(),
                    $user->getEmail(),
                    $user->getTelephone() ?? '',
                    $user->getRole(),
                    $user->getStatut(),
                    $user->getDateInscription()?->format('Y-m-d') ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}