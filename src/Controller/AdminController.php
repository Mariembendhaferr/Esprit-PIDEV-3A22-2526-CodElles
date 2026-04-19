<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Reclamation;
use App\Entity\ReclamationResponse;
use App\Service\TextRazorSentimentAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $em, TextRazorSentimentAnalyzer $sentimentAnalyzer): Response
    {
        $avisRepo = $em->getRepository(Avis::class);
        $reclamationRepo = $em->getRepository(Reclamation::class);
        $responseRepo = $em->getRepository(ReclamationResponse::class);

        /** @var Avis[] $avisList */
        $avisList = $avisRepo->findBy([], ['dateAvis' => 'DESC']);
        $avisTotal = \count($avisList);

        /** @var Reclamation[] $reclamationList */
        $reclamationList = $reclamationRepo->findBy([], ['dateCreation' => 'DESC']);
        $reclamationTotal = \count($reclamationList);

        $responseTotal = $responseRepo->count([]);

        $sentimentStats = [
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
            'avg' => 0.0,
            'scored' => 0,
        ];
        $worstAvis = [];

        foreach ($avisList as $avis) {
            $textScore = $sentimentAnalyzer->scoreText($avis->getCommentaire());
            $score = $textScore ?? 0.0;

            // Stabilize sentiment with the explicit rating when available.
            $note = $avis->getNote();
            if (null !== $note) {
                $noteScore = ((float) $note - 3.0) / 2.0; // 1..5 -> -1..+1
                $score = (0.65 * $noteScore) + (0.35 * $score);
            }

            if (null === $textScore && null === $note) {
                continue;
            }

            ++$sentimentStats['scored'];
            $sentimentStats['avg'] += $score;

            if ($score > 0.15) {
                ++$sentimentStats['positive'];
            } elseif ($score < -0.15) {
                ++$sentimentStats['negative'];
            } else {
                ++$sentimentStats['neutral'];
            }

            $worstAvis[] = ['avis' => $avis, 'score' => $score];
        }

        if ($sentimentStats['scored'] > 0) {
            $sentimentStats['avg'] /= $sentimentStats['scored'];
        }

        usort(
            $worstAvis,
            static fn (array $a, array $b): int => $a['score'] <=> $b['score']
        );
        $worstAvis = array_slice($worstAvis, 0, 5);

        $statusStats = [
            'En attente' => 0,
            'En cours' => 0,
            'Traité' => 0,
        ];
        foreach ($reclamationList as $r) {
            $status = $r->getStatut();
            if (isset($statusStats[$status])) {
                ++$statusStats[$status];
            }
        }

        $noteStats = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5' => 0,
            'none' => 0,
        ];
        foreach ($avisList as $avis) {
            $note = $avis->getNote();
            if (null === $note) {
                ++$noteStats['none'];
            } elseif (isset($noteStats[(string) $note])) {
                ++$noteStats[(string) $note];
            }
        }

        $responseRate = 0.0;
        if ($reclamationTotal > 0) {
            $responseRate = min(100.0, ($responseTotal / $reclamationTotal) * 100.0);
        }

        return $this->render('admin/dashboard.html.twig', [
            'avis_total' => $avisTotal,
            'reclamation_total' => $reclamationTotal,
            'response_total' => $responseTotal,
            'response_rate' => $responseRate,
            'status_stats' => $statusStats,
            'note_stats' => $noteStats,
            'sentiment' => $sentimentStats,
            'worst_avis' => $worstAvis,
            'charts' => [
                'sentiment_labels' => ['Positifs', 'Neutres', 'Négatifs'],
                'sentiment_values' => [
                    $sentimentStats['positive'],
                    $sentimentStats['neutral'],
                    $sentimentStats['negative'],
                ],
                'status_labels' => array_keys($statusStats),
                'status_values' => array_values($statusStats),
                'note_labels' => ['1★', '2★', '3★', '4★', '5★', 'Sans note'],
                'note_values' => [
                    $noteStats['1'],
                    $noteStats['2'],
                    $noteStats['3'],
                    $noteStats['4'],
                    $noteStats['5'],
                    $noteStats['none'],
                ],
            ],
        ]);
    }

    #[Route('/utilisateurs', name: 'app_admin_utilisateurs')]
    public function utilisateurs(): Response
    {
        return $this->render('admin/utilisateurs.html.twig');
    }

    #[Route('/voyages', name: 'app_admin_voyages')]
    public function voyages(): Response
    {
        return $this->render('admin/voyages.html.twig');
    }

    #[Route('/activites', name: 'app_admin_activites')]
    public function activites(): Response
    {
        return $this->render('admin/activites.html.twig');
    }

    #[Route('/reservations', name: 'app_admin_reservations')]
    public function reservations(): Response
    {
        return $this->render('admin/reservations.html.twig');
    }

}
