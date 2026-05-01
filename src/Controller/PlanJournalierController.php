<?php

namespace App\Controller;

use App\Entity\Planjournalier;
use App\Entity\Voyage;
use App\Repository\ActiviteRepository;
use App\Repository\PlanjournalierRepository;
use App\Repository\VoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanJournalierController extends AbstractController
{
    #[Route('/admin/voyages/voir-plan/{id}', name: 'app_voyage_voir_plan')]
    public function voirPlan(int $id, VoyageRepository $voyageRepo, PlanjournalierRepository $planRepo, ActiviteRepository $activiteRepo): Response
    {
        $voyage = $voyageRepo->find($id);
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }
        // Fix: $voyage est Voyage — getDestination() accessible
        $planJournaliers = $planRepo->findPlanJournalierWithActiviteByVoyageId($id);
        $activites = $activiteRepo->findByDestination($voyage->getDestination() ?? '');

        return $this->render('admin/voir_plan.html.twig', [
            'active_menu'     => 'voyages',
            'voyage'          => $voyage,
            'planJournaliers' => $planJournaliers,
            'activites'       => $activites,
            'allVoyages'      => $voyageRepo->findAll(),
        ]);
    }

   #[Route('/admin/activites/par-destination', name: 'app_activites_par_destination', methods: ['GET'])]
public function activitesParDestination(Request $request, ActiviteRepository $activiteRepo): JsonResponse
{
    $destination = (string) $request->query->get('destination', '');
    if (empty($destination)) {
        return $this->json([]);
    }
    $activites = $activiteRepo->findByDestination($destination);
    $data = array_map(function ($activite): array {
        /** @var \App\Entity\Activite $activite */
        return ['idActivite' => $activite->getId(), 'nomActivite' => $activite->getNomActivite()];
    }, $activites);
    return $this->json($data);
}

    #[Route('/admin/voyages/plan-journalier/{id}/modifier', name: 'app_plan_journalier_modifier', methods: ['POST'])]
public function modifier(int $id, Request $request, PlanjournalierRepository $planRepo, ActiviteRepository $activiteRepo, EntityManagerInterface $em): Response
{
    $plan = $planRepo->find($id);
    if (!$plan) {
        throw $this->createNotFoundException('Plan journalier introuvable.');
    }

    $titreJour = trim((string) $request->request->get('titre_jour', ''));
    if (!empty($titreJour)) {
        $plan->setTitreJour($titreJour);
    }

    $activiteId = $request->request->get('activite_id');
    if ($activiteId !== null && $activiteId !== '') {
        /** @var \App\Entity\Activite|null $activite */
        $activite = $activiteRepo->find((int) $activiteId);
        if ($activite instanceof \App\Entity\Activite) {
            $plan->setActivite($activite);
        } else {
            $plan->setActivite(null);
        }
    } else {
        $plan->setActivite(null);
    }

    $em->flush();

    $voyage = $plan->getVoyage();
    if (!$voyage instanceof Voyage) {
        throw $this->createNotFoundException('Voyage associé introuvable.');
    }

    $this->addFlash('success', 'La journée a été mise à jour avec succès.');
    return $this->redirectToRoute('app_voyage_voir_plan', ['id' => $voyage->getIdVoyage()]);
}

    #[Route('/admin/voyages/planifier/{id}', name: 'app_voyage_planifier')]
    public function planifier(int $id, VoyageRepository $voyageRepo, ActiviteRepository $activiteRepo): Response
    {
        $voyage = $voyageRepo->find($id);
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }
        $activites = $activiteRepo->findByDestination($voyage->getDestination() ?? '');
        return $this->render('admin/planifier_sejour.html.twig', [
            'active_menu' => 'voyages',
            'voyage'      => $voyage,
            'activites'   => $activites,
            'allVoyages'  => $voyageRepo->findAll(),
        ]);
    }

    #[Route('/admin/voyages/planifier/{id}/save', name: 'app_voyage_planifier_save', methods: ['POST'])]
public function savePlan(int $id, Request $request, VoyageRepository $voyageRepo, ActiviteRepository $activiteRepo, EntityManagerInterface $em): Response
{
    $voyage = $voyageRepo->find($id);
    if (!$voyage) {
        throw $this->createNotFoundException('Voyage introuvable.');
    }

    $duree = $voyage->getDuree() ?? 0;
    $savedCount = 0;

    for ($i = 1; $i <= $duree; $i++) {
        $theme = trim((string) ($request->request->get('jour_' . $i . '_theme') ?? ''));
        $activiteId = $request->request->get('jour_' . $i . '_activite_id');

        if (!empty($theme)) {
            $existingPlan = $em->getRepository(Planjournalier::class)->findOneBy(['voyage' => $voyage, 'jour' => $i]);
            $planJournalier = $existingPlan instanceof Planjournalier ? $existingPlan : new Planjournalier();

            if (!$existingPlan instanceof Planjournalier) {
                $planJournalier->setVoyage($voyage);
                $planJournalier->setJour($i);
            }

            $planJournalier->setTitreJour($theme);

            if (!empty($activiteId)) {
                /** @var \App\Entity\Activite|null $activite */
                $activite = $activiteRepo->find((int) $activiteId);
                if ($activite instanceof \App\Entity\Activite) {
                    $planJournalier->setActivite($activite);
                }
            }

            $em->persist($planJournalier);
            $savedCount++;
        }
    }

    $em->flush();
    $this->addFlash('success', "✅ {$savedCount} jours ont été planifiés avec succès pour {$voyage->getDestination()} !");
    return $this->redirectToRoute('app_voyage_voir_plan', ['id' => $voyage->getIdVoyage()]);
}
}