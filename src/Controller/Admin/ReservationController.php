<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use App\Entity\Paiement;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\ClientRepository;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReservationController extends AbstractController
{
    #[Route('/admin/reservations', name: 'admin_reservations')]
    public function index(
        Request $request,
        ReservationRepository $reservationRepo,
        ClientRepository $clientRepo,
        PaiementRepository $paiementRepo
    ): Response {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $dateDebut = $request->query->get('date_debut') ? new \DateTime($request->query->get('date_debut')) : null;
        $dateFin = $request->query->get('date_fin') ? new \DateTime($request->query->get('date_fin')) : null;

        $allReservationsFiltered = $reservationRepo->findByFilters($search, $statut, $dateDebut, $dateFin);

        // Calcul du chiffre d'affaires réel (montants payés)
        $chiffreAffaire = 0;
        foreach ($allReservationsFiltered as $r) {
            $paiements = $paiementRepo->findBy(['reservation' => $r]);
            $montantPaye = $paiements ? $paiements[0]->getMontant() : $r->getMontantTotal();
            $chiffreAffaire += $montantPaye;
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $reservations = array_slice($allReservationsFiltered, $offset, $limit);

        $totalReservations = count($allReservationsFiltered);
        $totalPages = ceil($totalReservations / $limit);
        $totalPaiements = count($paiementRepo->findAll());
        $totalClients = count($clientRepo->findAll());

        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'chiffreAffaire' => $chiffreAffaire,
            'totalReservations' => $totalReservations,
            'totalPaiements' => $totalPaiements,
            'totalClients' => $totalClients,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
        ]);
    }

    #[Route('/admin/reservations/{id}/detail', name: 'admin_reservation_detail')]
    public function detail(Reservation $reservation, PaiementRepository $paiementRepo): Response
    {
        $paiements = $paiementRepo->findBy(['reservation' => $reservation]);

        return $this->render('admin/reservation/detail.html.twig', [
            'reservation' => $reservation,
            'paiements' => $paiements,
        ]);
    }

    #[Route('/admin/reservations/{id}/edit', name: 'admin_reservation_edit')]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->calculerMontant();
            $em->flush();
            $this->addFlash('success', 'Réservation modifiée avec succès.');
            return $this->redirectToRoute('admin_reservations');
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    #[Route('/admin/reservations/{id}/delete', name: 'admin_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$reservation->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_reservations');
        }

        $paiement = $em->getRepository(Paiement::class)->findOneBy(['reservation' => $reservation]);

        if ($reservation->getStatut() === 'annulé') {
            try {
                if ($paiement) $em->remove($paiement);
                $em->remove($reservation);
                $em->flush();
                $this->addFlash('success', 'Réservation annulée supprimée.');
                return $this->redirectToRoute('admin_reservations');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur technique.');
                return $this->redirectToRoute('admin_reservations');
            }
        }

        if ($paiement) {
            $this->addFlash('error', 'Impossible de supprimer une réservation avec un paiement.');
            return $this->redirectToRoute('admin_reservations');
        }

        if ($reservation->getStatut() === 'payé') {
            $this->addFlash('error', 'Impossible de supprimer une réservation payée.');
            return $this->redirectToRoute('admin_reservations');
        }

        try {
            $em->remove($reservation);
            $em->flush();
            $this->addFlash('success', 'Réservation supprimée.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur technique.');
        }

        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/admin/reservations/export-pdf', name: 'admin_reservations_export_pdf')]
    public function exportPdf(
        Request $request,
        ReservationRepository $reservationRepo,
        ClientRepository $clientRepo,
        PaiementRepository $paiementRepo
    ): Response {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $dateDebut = $request->query->get('date_debut') ? new \DateTime($request->query->get('date_debut')) : null;
        $dateFin = $request->query->get('date_fin') ? new \DateTime($request->query->get('date_fin')) : null;

        $reservations = $reservationRepo->findByFilters($search, $statut, $dateDebut, $dateFin);

        $chiffreAffaire = 0;
        foreach ($reservations as $r) {
            $paiements = $paiementRepo->findBy(['reservation' => $r]);
            $montantPaye = $paiements ? $paiements[0]->getMontant() : $r->getMontantTotal();
            $chiffreAffaire += $montantPaye;
        }

        $totalReservations = count($reservations);
        $totalPaiements = count($paiementRepo->findAll());
        $totalClients = count($clientRepo->findAll());

        $html = $this->renderView('admin/reservation/export_pdf.html.twig', [
            'reservations' => $reservations,
            'chiffreAffaire' => $chiffreAffaire,
            'totalReservations' => $totalReservations,
            'totalPaiements' => $totalPaiements,
            'totalClients' => $totalClients,
            'search' => $search,
            'statut' => $statut,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
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
                'Content-Disposition' => 'attachment; filename="reservations_' . date('Y-m-d') . '.pdf"',
            ]
        );
    }

    #[Route('/admin/reservations/export-csv', name: 'admin_reservations_export_csv')]
    public function exportCsv(
        Request $request,
        ReservationRepository $reservationRepo,
        PaiementRepository $paiementRepo
    ): Response {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $dateDebut = $request->query->get('date_debut') ? new \DateTime($request->query->get('date_debut')) : null;
        $dateFin = $request->query->get('date_fin') ? new \DateTime($request->query->get('date_fin')) : null;

        $reservations = $reservationRepo->findByFilters($search, $statut, $dateDebut, $dateFin);

        $csvContent = "ID;Client;Email;Téléphone;Destination;Date départ;Date retour;Personnes;Montant initial;Montant payé;Réduction;Statut\n";

        foreach ($reservations as $r) {
            $paiements = $paiementRepo->findBy(['reservation' => $r]);
            $montantPaye = $paiements ? $paiements[0]->getMontant() : null;
            $reduction = ($montantPaye && $montantPaye < $r->getMontantTotal()) ? round((1 - $montantPaye / $r->getMontantTotal()) * 100) . '%' : '-';

            $csvContent .= '#DMT' . $r->getId() . ';'
                         . $r->getClient()->getPrenom() . ' ' . $r->getClient()->getNom() . ';'
                         . $r->getClient()->getEmail() . ';'
                         . $r->getClient()->getTelephone() . ';'
                         . $r->getVoyage()->getDestination() . ';'
                         . $r->getDateDepart()->format('d/m/Y') . ';'
                         . $r->getDateRetour()->format('d/m/Y') . ';'
                         . $r->getNombrePersonnes() . ';'
                         . $r->getMontantTotal() . ' €;'
                         . ($montantPaye ? $montantPaye . ' €' : 'Non payé') . ';'
                         . $reduction . ';'
                         . ucfirst($r->getStatut()) . "\n";
        }

        return new Response(
            $csvContent,
            200,
            [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="reservations_' . date('Y-m-d') . '.csv"',
            ]
        );
    }
}