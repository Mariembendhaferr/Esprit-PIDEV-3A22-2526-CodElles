<?php

namespace App\Controller;

use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatistiqueController extends AbstractController
{
    #[Route('/admin/statistiques', name: 'app_statistiques')]
    public function index(VoyageRepository $voyageRepo): Response
    {
        // --- KPIs globaux ---
        $totalVoyages       = $voyageRepo->countTotalVoyages();
        $budgetMoyenGlobal  = $voyageRepo->getBudgetMoyenGlobal();
        $dureeMoyenneGlobal = $voyageRepo->getDureeMoyenneGlobal();
        $topDestination     = $voyageRepo->getTopDestination();

        // --- Graphique 1 : Répartition par continent (camembert) ---
        $repartitionContinents = $voyageRepo->getRepartitionParContinent();

        // --- Graphique 2 : Budget moyen par continent (barres) ---
        $budgetParContinent = $voyageRepo->getBudgetMoyenParContinent();

        // --- Graphique 3 : Durée moyenne par continent (courbe) ---
        $dureeParContinent = $voyageRepo->getDureeMoyenneParContinent();

        // --- Graphique 4 : Top 8 destinations par nb voyages (barres horizontales) ---
        $topDestinations = $voyageRepo->getTopDestinations(8);

        // --- Graphique 5 : Nb personnes total par continent (barres empilées) ---
        $personnesParContinent = $voyageRepo->getNbPersonnesParContinent();

        return $this->render('admin/Statistique_index.html.twig', [
            // KPIs
            'totalVoyages'        => $totalVoyages,
            'budgetMoyenGlobal'   => round($budgetMoyenGlobal, 2),
            'dureeMoyenneGlobal'  => round($dureeMoyenneGlobal, 1),
            'topDestination'      => $topDestination,

            // Données graphiques (encodées JSON pour Twig → JS)
            'repartitionContinents' => json_encode($repartitionContinents),
            'budgetParContinent'    => json_encode($budgetParContinent),
            'dureeParContinent'     => json_encode($dureeParContinent),
            'topDestinations'       => json_encode($topDestinations),
            'personnesParContinent' => json_encode($personnesParContinent),
        ]);
    }
}