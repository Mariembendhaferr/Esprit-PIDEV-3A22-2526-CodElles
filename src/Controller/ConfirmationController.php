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

    // ================== ADMIN : GESTION DES CLIENTS ==================
    #[Route('/admin/clients', name: 'app_admin_clients')]
    public function adminList(EntityManagerInterface $em): Response
    {
        $clients = $em->getRepository(Client::class)->findBy([], ['id' => 'DESC']);
        return $this->render('client/admin_list.html.twig', ['clients' => $clients]);
    }

    #[Route('/admin/client/new', name: 'app_admin_client_new')]
    public function adminNew(Request $request, EntityManagerInterface $em): Response
    {
        $client = new Client();
        $form = $this->createFormBuilder($client)
            ->add('nom', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('prenom', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('dateNaissance', DateType::class, ['widget' => 'single_text', 'attr' => ['class' => 'form-control']])
            ->add('email', EmailType::class, ['attr' => ['class' => 'form-control']])
            ->add('telephone', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('points', IntegerType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $existing = $em->getRepository(Client::class)->findOneBy(['email' => $client->getEmail()]);
            if ($existing) {
                $this->addFlash('error', 'Cet email existe déjà.');
            } else {
                $em->persist($client);
                $em->flush();
                $this->addFlash('success', 'Client créé.');
                return $this->redirectToRoute('app_admin_clients');
            }
        }

        return $this->render('client/admin_new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/client/{id}/edit', name: 'app_admin_client_edit')]
    public function adminEdit(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder($client)
            ->add('nom', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('prenom', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('dateNaissance', DateType::class, ['widget' => 'single_text', 'attr' => ['class' => 'form-control']])
            ->add('email', EmailType::class, ['attr' => ['class' => 'form-control']])
            ->add('telephone', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('points', IntegerType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Client modifié.');
            return $this->redirectToRoute('app_admin_clients');
        }

        return $this->render('client/admin_edit.html.twig', [
            'form' => $form->createView(),
            'client' => $client,
        ]);
    }

    #[Route('/admin/client/{id}/delete', name: 'app_admin_client_delete', methods: ['POST'])]
    public function adminDelete(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->request->get('_token'))) {
            $reservations = $em->getRepository(\App\Entity\Reservation::class)->findBy(['client' => $client]);
            if (count($reservations) > 0) {
                $this->addFlash('error', 'Ce client a des réservations, suppression impossible.');
            } else {
                $em->remove($client);
                $em->flush();
                $this->addFlash('success', 'Client supprimé.');
            }
        }
        return $this->redirectToRoute('app_admin_clients');
    }
}