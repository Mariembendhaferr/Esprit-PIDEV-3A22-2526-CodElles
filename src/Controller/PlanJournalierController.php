<?php

namespace App\Controller;

use App\Entity\Planjournalier;
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
    // ==================== ADMIN : VOIR LE PLAN D'UN VOYAGE ====================

    #[Route('/admin/voyages/voir-plan/{id}', name: 'app_voyage_voir_plan')]
    public function voirPlan(
        int $id,
        VoyageRepository $voyageRepo,
        PlanjournalierRepository $planRepo
    ): Response {
        $voyage = $voyageRepo->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $planJournaliers = $planRepo->findPlanJournalierWithActiviteByVoyageId($id);

        return $this->render('admin/voir_plan.html.twig', [
            'active_menu'     => 'voyages',
            'voyage'          => $voyage,
            'planJournaliers' => $planJournaliers,
        ]);
    }

    // ==================== AJAX : ACTIVITÉS PAR DESTINATION ====================

    #[Route('/admin/activites/par-destination', name: 'app_activites_par_destination', methods: ['GET'])]
    public function activitesParDestination(
        Request $request,
        ActiviteRepository $activiteRepo
    ): JsonResponse {
        $destination = $request->query->get('destination', '');

        if (empty($destination)) {
            return $this->json([]);
        }

        // Récupère les activités dont la localisation correspond à la destination du voyage
        $activites = $activiteRepo->createQueryBuilder('a')
            ->where('a.localisationActivite LIKE :dest')
            ->setParameter('dest', '%' . $destination . '%')
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(function ($activite) {
            return [
                'idActivite'  => $activite->getIdActivite(),
                'nomActivite' => $activite->getNomActivite(),
            ];
        }, $activites);

        return $this->json($data);
    }

    // ==================== ADMIN : MODIFIER UN PLAN JOURNALIER ====================

    #[Route('/admin/voyages/plan-journalier/{id}/modifier', name: 'app_plan_journalier_modifier', methods: ['POST'])]
    public function modifier(
        int $id,
        Request $request,
        PlanjournalierRepository $planRepo,
        ActiviteRepository $activiteRepo,
        EntityManagerInterface $em
    ): Response {
        $plan = $planRepo->find($id);

        if (!$plan) {
            throw $this->createNotFoundException('Plan journalier introuvable.');
        }

        // Mettre à jour le titre
        $titreJour = trim($request->request->get('titre_jour', ''));
        if (!empty($titreJour)) {
            $plan->setTitreJour($titreJour);
        }

        // Mettre à jour l'activité
        $activiteId = $request->request->get('activite_id');
        if ($activiteId) {
            $activite = $activiteRepo->find((int) $activiteId);
            if ($activite) {
                $plan->setActivite($activite);
            }
        } else {
            $plan->setActivite(null);
        }

        $em->flush();

        // Récupérer l'id du voyage pour la redirection
        $voyageId = $plan->getVoyage()->getIdVoyage();

        $this->addFlash('success', 'La journée a été mise à jour avec succès.');

        return $this->redirectToRoute('app_voyage_voir_plan', ['id' => $voyageId]);
    }

   // ==================== PAGE DE PLANIFICATION (AFFICHAGE) ====================

#[Route('/admin/voyages/planifier/{id}', name: 'app_voyage_planifier')]
public function planifier(int $id, VoyageRepository $voyageRepo, ActiviteRepository $activiteRepo): Response
{
    $voyage = $voyageRepo->find($id);

    if (!$voyage) {
        throw $this->createNotFoundException('Voyage introuvable.');
    }

    $destination = $voyage->getDestination();

    // Requête directe sans passer par findByDestination
    $activites = $activiteRepo->createQueryBuilder('a')
        ->where('a.localisationActivite LIKE :dest')
        ->setParameter('dest', '%' . $destination . '%')
        ->orderBy('a.nomActivite', 'ASC')
        ->getQuery()
        ->getResult();

    return $this->render('admin/planifier_sejour.html.twig', [
        'active_menu' => 'voyages',
        'voyage'      => $voyage,
        'activites'   => $activites,
    ]);
}


// ==================== SAUVEGARDER LE PLAN JOURNALIER ====================

#[Route('/admin/voyages/planifier/{id}/save', name: 'app_voyage_planifier_save', methods: ['POST'])]
public function savePlan(
    int $id,
    Request $request,
    VoyageRepository $voyageRepo,
    ActiviteRepository $activiteRepo,
    EntityManagerInterface $em
): Response {
    $voyage = $voyageRepo->find($id);
    
    if (!$voyage) {
        throw $this->createNotFoundException('Voyage introuvable.');
    }
    
    // Récupérer la durée du voyage
    $duree = $voyage->getDuree();
    $savedCount = 0;
    
    // Pour chaque jour, créer ou mettre à jour le plan journalier
    for ($i = 1; $i <= $duree; $i++) {
        $theme = $request->request->get('jour_' . $i . '_theme');
        $activiteId = $request->request->get('jour_' . $i . '_activite_id');
        
        // Ne créer que si le thème est rempli
        if ($theme) {
            // Vérifier si un plan existe déjà pour ce jour
            $existingPlan = $em->getRepository(Planjournalier::class)->findOneBy([
                'voyage' => $voyage,
                'jour' => $i
            ]);
            
            if ($existingPlan) {
                $planJournalier = $existingPlan;
            } else {
                $planJournalier = new Planjournalier();
                $planJournalier->setVoyage($voyage);
                $planJournalier->setJour($i);
            }
            
            $planJournalier->setTitreJour($theme);
            
            // 🔥 Associer l'activité si un ID est fourni
            if ($activiteId && $activiteId !== '') {
                $activite = $activiteRepo->find((int) $activiteId);
                if ($activite) {
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