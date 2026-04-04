<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfService
{
    private Dompdf $dompdf;
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $options = new Options();
        $options->set('defaultFont', 'Courier');
        $options->set('isRemoteEnabled', true);
        $this->dompdf = new Dompdf($options);
        $this->twig = $twig;
    }

    public function generateActivitesPdf(array $activites, array $filters = []): Response
    {
        $html = $this->twig->render('pdf/activites_export.html.twig', [
            'activites' => $activites,
            'filters' => $filters,
            'generatedAt' => new \DateTime(),
        ]);

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'landscape');
        $this->dompdf->render();

        $pdf = $this->dompdf->output();

        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="activites_export_' . date('Y-m-d_H-i-s') . '.pdf"'
            ]
        );
    }
}
