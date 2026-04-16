<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Reservation;
use App\Form\ClientType;
use App\Form\ReservationType;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UserReservationController extends AbstractController
{
    // ================== RÉSERVATION PUBLIQUE ==================
    #[Route('/reserver', name: 'app_reservation')]
    public function index(Request $request, EntityManagerInterface $em, VoyageRepository $voyageRepo): Response
    {
        $client = new Client();
        $reservation = new Reservation();

        $clientForm = $this->createForm(ClientType::class, $client);
        $reservationForm = $this->createForm(ReservationType::class, $reservation);

        // On ne fait pas handleRequest tout de suite car ils sont dans un seul formulaire
        // On va traiter les données après soumission

        if ($request->isMethod('POST')) {
            // On remplit les deux formulaires avec les données POST
            $clientForm->handleRequest($request);
            $reservationForm->handleRequest($request);

            $clientValid = $clientForm->isSubmitted() && $clientForm->isValid();
            $reservationValid = $reservationForm->isSubmitted() && $reservationForm->isValid();

            // Affichage des erreurs de validation (débogage)
            if (!$clientValid) {
                $errors = (string) $clientForm->getErrors(true, false);
                $this->addFlash('error', 'Erreur client : ' . $errors);
            }
            if (!$reservationValid) {
                $errors = (string) $reservationForm->getErrors(true, false);
                $this->addFlash('error', 'Erreur réservation : ' . $errors);
            }

            if ($clientValid && $reservationValid) {
                // Vérifier si le client existe déjà par email
                $existingClient = $em->getRepository(Client::class)->findOneBy(['email' => $client->getEmail()]);
                if ($existingClient) {
                    $client = $existingClient;
                } else {
                    $em->persist($client);
                    $em->flush(); // Pour avoir l'ID
                }

                $reservation->setClient($client);
                $reservation->setDateReservation(new \DateTime());
                $reservation->calculerMontant();

                // Vérifier que le montant total a bien été calculé
                if ($reservation->getMontantTotal() === null) {
                    $this->addFlash('error', 'Erreur de calcul du montant. Vérifiez les dates et le voyage.');
                    return $this->render('reservation/form.html.twig', [
                        'client_form' => $clientForm->createView(),
                        'reservation_form' => $reservationForm->createView(),
                    ]);
                }

                try {
                    $em->persist($reservation);
                    $em->flush();
                    $this->addFlash('success', 'Réservation enregistrée !');
                    return $this->redirectToRoute('app_payment', ['id' => $reservation->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur base de données : ' . $e->getMessage());
                }
            }
        }

        return $this->render('reservation/form.html.twig', [
            'client_form' => $clientForm->createView(),
            'reservation_form' => $reservationForm->createView(),
        ]);
    }
}