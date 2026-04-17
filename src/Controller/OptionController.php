<?php
// src/Controller/OptionController.php

namespace App\Controller;

use App\Entity\Voyage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OptionController extends AbstractController
{
    #[Route('/api/options/{id}', name: 'app_options_api', methods: ['GET'])]
    public function getOptions(Voyage $voyage): JsonResponse
    {
        // Options incluses par défaut dans le forfait
        $inclus = [
            ['nom' => 'Hébergement 5 étoiles', 'description' => 'Nuits dans des palaces sélectionnés', 'icon' => 'hotel'],
            ['nom' => 'Guide certifié', 'description' => 'Accompagnateur francophone expert', 'icon' => 'user-tie'],
            ['nom' => 'Transferts aéroport', 'description' => 'Aller/retour en véhicule privé', 'icon' => 'car'],
            ['nom' => 'Petit-déjeuner', 'description' => 'Buffet quotidien inclus', 'icon' => 'coffee'],
            ['nom' => 'Assistance 24/7', 'description' => 'Support dédié pendant tout le séjour', 'icon' => 'headset']
        ];
        
        // Options supplémentaires payantes
        $optionsPayantes = [
            [
                'id' => 'transport_all',
                'nom' => 'Transport sur toute la durée',
                'description' => 'Voiture privée avec chauffeur pour tous les déplacements',
                'prix' => 850,
                'prix_par_personne' => false,
                'icon' => 'car-side',
                'recommande' => true
            ],
            [
                'id' => 'restaurants',
                'nom' => 'Dîners gastronomiques',
                'description' => 'Dîners dans les meilleurs restaurants étoilés',
                'prix' => 450,
                'prix_par_personne' => true,
                'icon' => 'utensils',
                'recommande' => true
            ],
            [
                'id' => 'activites_supp',
                'nom' => 'Activités exclusives',
                'description' => 'Accès à des expériences privées et VIP',
                'prix' => 320,
                'prix_par_personne' => true,
                'icon' => 'mountain',
                'recommande' => false
            ],
            [
                'id' => 'assurance_annulation',
                'nom' => 'Assurance annulation',
                'description' => 'Annulation sans frais jusqu\'à 7 jours avant',
                'prix' => 120,
                'prix_par_personne' => true,
                'icon' => 'shield-alt',
                'recommande' => false
            ],
            [
                'id' => 'extension_sejour',
                'nom' => 'Extension de séjour (+3 nuits)',
                'description' => 'Prolongez votre expérience',
                'prix' => 890,
                'prix_par_personne' => false,
                'icon' => 'calendar-plus',
                'recommande' => false
            ],
            [
                'id' => 'vols_internationaux',
                'nom' => 'Vols internationaux',
                'description' => 'Billets d\'avion aller-retour classe économique',
                'prix' => 1200,
                'prix_par_personne' => true,
                'icon' => 'plane',
                'recommande' => false
            ]
        ];
        
        return $this->json([
            'success' => true,
            'prix_base' => $voyage->getBudgetEstime(),
            'inclus' => $inclus,
            'options' => $optionsPayantes,
            'nb_personnes' => $voyage->getNbPersonnes()
        ]);
    }
    
    #[Route('/api/options/calculer', name: 'app_options_calculer', methods: ['POST'])]
    public function calculerPrix(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $prixBase = $data['prix_base'] ?? 0;
        $optionsSelectionnees = $data['options'] ?? [];
        $optionsData = $data['options_data'] ?? [];
        $nbPersonnes = $data['nb_personnes'] ?? 1;
        
        $totalSupplements = 0;
        
        foreach ($optionsSelectionnees as $optionId) {
            foreach ($optionsData as $option) {
                if ($option['id'] === $optionId) {
                    $prixOption = $option['prix'];
                    if ($option['prix_par_personne']) {
                        $prixOption *= $nbPersonnes;
                    }
                    $totalSupplements += $prixOption;
                }
            }
        }
        
        $prixTotal = $prixBase + $totalSupplements;
        
        return $this->json([
            'success' => true,
            'prix_base' => $prixBase,
            'total_supplements' => $totalSupplements,
            'prix_total' => $prixTotal,
            'prix_par_personne' => round($prixTotal / $nbPersonnes)
        ]);
    }
}