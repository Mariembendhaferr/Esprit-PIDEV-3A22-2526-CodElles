<?php

namespace App\Service;

use App\Entity\Reclamation;
use App\Entity\ReclamationResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class ReclamationHistoriquePdfGenerator
{
    public function __construct(private Environment $twig)
    {
    }

    /**
     * @param Reclamation[]                                     $reclamations
     * @param array<int, ReclamationResponse[]>                 $responsesByReclamation
     * @param array{q: string, statut: string, priorite: string} $filters
     */
    public function buildPdfContent(array $reclamations, array $responsesByReclamation, array $filters): string
    {
        $sortedResponses = [];
        foreach ($responsesByReclamation as $rid => $list) {
            usort($list, static function (ReclamationResponse $a, ReclamationResponse $b): int {
                $da = $a->getDateResponse();
                $db = $b->getDateResponse();
                if ($da == $db) {
                    return 0;
                }
                if (null === $da) {
                    return 1;
                }
                if (null === $db) {
                    return -1;
                }

                return $da <=> $db;
            });
            $sortedResponses[$rid] = $list;
        }

        $html = $this->twig->render('admin/reclamation/historique_pdf.html.twig', [
            'reclamations' => $reclamations,
            'responses_by_reclamation' => $sortedResponses,
            'filter_q' => $filters['q'] ?? '',
            'filter_statut' => $filters['statut'] ?? '',
            'filter_priorite' => $filters['priorite'] ?? '',
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
