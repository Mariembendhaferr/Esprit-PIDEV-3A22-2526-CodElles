<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Form\PaymentType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PaymentController extends AbstractController
{
    // ================== PAIEMENT PUBLIC ==================
    #[Route('/paiement/{id}', name: 'app_payment')]
    public function index(int $id, Request $request, ReservationRepository $reservationRepo, EntityManagerInterface $em): Response
    {
        $reservation = $reservationRepo->find($id);
        if (!$reservation) throw $this->createNotFoundException('Réservation introuvable');

        if ($reservation->getStatut() === 'payé') {
            $this->addFlash('info', 'Déjà payé.');
            return $this->redirectToRoute('app_confirmation', ['id' => $id]);
        }

        $paiement = new Paiement();
        $form = $this->createForm(PaymentType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $paiement->setReservation($reservation);
            $paiement->setMontant($reservation->getMontantTotal());
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setReferencePaiement('CARTE-'.time().'-'.$reservation->getId());
            $paiement->setStatut('payé');
            $paiement->setTypePaiement('Complet');

            $em->persist($paiement);
            $reservation->setStatut('payé');
            $em->flush();

            return $this->redirectToRoute('app_confirmation', ['id' => $reservation->getId()]);
        }

        return $this->render('payment/form.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    // ================== ADMIN : GESTION DES PAIEMENTS ==================
    #[Route('/admin/paiements', name: 'app_admin_paiements')]
    public function adminList(EntityManagerInterface $em): Response
    {
        $paiements = $em->getRepository(Paiement::class)->findBy([], ['id' => 'DESC']);
        return $this->render('payment/admin_list.html.twig', ['paiements' => $paiements]);
    }

    #[Route('/admin/paiement/new', name: 'app_admin_paiement_new')]
    public function adminNew(Request $request, EntityManagerInterface $em): Response
    {
        $paiement = new Paiement();
        $form = $this->createFormBuilder($paiement)
            ->add('reservation', EntityType::class, [
                'class' => Reservation::class,
                'choice_label' => fn($r) => '#'.$r->getId().' - '.$r->getClient()->getNom().' '.$r->getClient()->getPrenom().' ('.$r->getMontantTotal().' €)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('modePaiement', ChoiceType::class, [
                'choices' => ['Carte Bancaire' => 'Carte Bancaire', 'PayPal' => 'PayPal', 'Virement' => 'Virement', 'Espèces' => 'Espèces'],
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $paiement->getReservation();
            if ($reservation->getStatut() === 'payé') {
                $this->addFlash('error', 'Réservation déjà payée.');
                return $this->redirectToRoute('app_admin_paiements');
            }
            $paiement->setMontant($reservation->getMontantTotal());
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setReferencePaiement('ADMIN-'.time().'-'.$reservation->getId());
            $paiement->setStatut('payé');
            $paiement->setTypePaiement('Complet');
            $em->persist($paiement);
            $reservation->setStatut('payé');
            $em->flush();
            $this->addFlash('success', 'Paiement enregistré.');
            return $this->redirectToRoute('app_admin_paiements');
        }

        return $this->render('payment/admin_new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/paiement/{id}/delete', name: 'app_admin_paiement_delete', methods: ['POST'])]
    public function adminDelete(Request $request, Paiement $paiement, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paiement->getId(), $request->request->get('_token'))) {
            $reservation = $paiement->getReservation();
            $reservation->setStatut('en attente');
            $em->remove($paiement);
            $em->flush();
            $this->addFlash('success', 'Paiement supprimé.');
        }
        return $this->redirectToRoute('app_admin_paiements');
    }
}