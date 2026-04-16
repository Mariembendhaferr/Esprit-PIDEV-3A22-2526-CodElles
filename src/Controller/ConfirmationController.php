<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\PaiementRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Dompdf\Dompdf;
use Dompdf\Options;

class ConfirmationController extends AbstractController
{
    // ================== CONFIRMATION PUBLIQUE ==================
    #[Route('/confirmation/{id}', name: 'app_confirmation')]
    public function index(int $id, ReservationRepository $reservationRepo, PaiementRepository $paiementRepo): Response
    {
        $reservation = $reservationRepo->find($id);
        if (!$reservation) throw $this->createNotFoundException('Réservation introuvable');

        $paiement = $paiementRepo->findOneBy(['reservation' => $reservation]);
        $client = $reservation->getClient();

        return $this->render('confirmation/index.html.twig', [
            'reservation' => $reservation,
            'paiement' => $paiement,
            'client' => $client,
            'destination' => $reservation->getVoyage()->getDestination(),
        ]);
    }

    // ================== TÉLÉCHARGER LA RÉSERVATION EN PDF ==================
    #[Route('/confirmation/{id}/download-pdf', name: 'app_confirmation_download_pdf')]
    public function downloadReservationPdf(int $id, ReservationRepository $reservationRepo, PaiementRepository $paiementRepo): Response
    {
        $reservation = $reservationRepo->find($id);
        if (!$reservation) throw $this->createNotFoundException('Réservation introuvable');

        $paiement = $paiementRepo->findOneBy(['reservation' => $reservation]);
        $client = $reservation->getClient();

        $html = $this->renderView('confirmation/download_pdf.html.twig', [
            'reservation' => $reservation,
            'paiement' => $paiement,
            'client' => $client,
            'destination' => $reservation->getVoyage()->getDestination(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reservation_DMT' . $reservation->getId() . '.pdf"',
            ]
        );
    }

   
}