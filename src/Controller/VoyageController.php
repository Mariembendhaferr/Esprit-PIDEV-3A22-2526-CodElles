<?php

namespace App\Controller;

use App\Repository\VoyageRepository;
use App\Repository\PlanjournalierRepository;
use App\Repository\ActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\VoyageType;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Repository\CommunityPostRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class VoyageController extends AbstractController
{
    // ✅ Fix: propriété typée (missingType.property)
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    // ==================== PAGE ACCUEIL ====================

    #[Route('/', name: 'app_accueil')]
    public function accueil(CommunityPostRepository $communityPostRepository, VoyageRepository $voyageRepository, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) {
            return $this->redirectToRoute('login');
        }
        $communityPosts = $communityPostRepository->findLatestApproved(9);
        $topVoyages = $voyageRepository->findBy([], ['budget_estime' => 'ASC'], 3);
        $totalPhotos = $communityPostRepository->countApprovedPhotos();
        $totalDestinations = $communityPostRepository->countUniqueDestinations();
        $totalVoyages = $voyageRepository->countAllWithoutCustom();
        $totalExperts = 5;

        return $this->render('voyage/index.html.twig', [
            'communityPosts'    => $communityPosts,
            'destinations_json' => json_encode(\App\Form\CommunityPostType::DESTINATIONS),
            'topVoyages'        => $topVoyages,
            'statsPhotos'       => $totalPhotos,
            'statsDestinations' => $totalDestinations,
            'statsVoyageurs'    => 23,
            'statsVoyages'      => $totalVoyages,
            'statsExperts'      => $totalExperts,
        ]);
    }

    // ==================== PAGE DESTINATIONS ====================

    #[Route('/destinations', name: 'app_destinations')]
    public function destinations(): Response
    {
        return $this->render('voyage/destinations.html.twig');
    }

    // ==================== PAGE INSPIRATIONS ====================

    #[Route('/inspirations', name: 'app_inspirations')]
    public function inspirations(VoyageRepository $repo): Response
    {
        $voyages = $repo->findAllWithoutCustom();
        $nbPays  = $repo->countDistinctPays();
        $taux    = $this->getTauxChange();

        return $this->render('voyage/inspirations.html.twig', [
            'voyages'      => $voyages,
            'nbVoyages'    => count($voyages),
            'nbPays'       => $nbPays,
            'nbContinents' => 5,
            'note'         => 4.8,
            'taux'         => $taux,
        ]);
    }

    // ==================== PAGE DETAIL D'UN PAYS ====================

    #[Route('/destination/{nom}', name: 'app_voyage_detail')]
    public function detail(string $nom, VoyageRepository $repo): Response
    {
        $pays   = ucfirst(strtolower($nom));
        $coords = $this->getCoordinates($pays);
        $voyages = $repo->findByDestinationWithoutCustom($pays);
        $descriptionWikipedia = $this->getWikipediaDescription($pays);
        $meteo  = $this->getMeteo($pays);
        $prixMin = null;

        foreach ($voyages as $voyage) {
            // ✅ Fix: $voyage est bien un objet Voyage, pas un object générique
            /** @var Voyage $voyage */
            $budget = $voyage->getBudgetEstime();
            if ($prixMin === null || ($budget !== null && $budget < $prixMin)) {
                $prixMin = $budget;
            }
        }

        $taux = $this->getTauxChange();

        return $this->render('voyage/detail-pays.html.twig', [
            'nomPays'              => $pays,
            'voyages'              => $voyages,
            'prixMin'              => $prixMin,
            'taux'                 => $taux,
            'descriptionWikipedia' => $descriptionWikipedia,
            'meteo'                => $meteo,
            'latitude'             => $coords['lat'],
            'longitude'            => $coords['lng'],
            'zoom'                 => $coords['zoom'],
        ]);
    }

    // ==================== API WIKIPEDIA ====================

    private function getWikipediaDescription(string $pays): string
    {
        try {
            $url      = 'https://fr.wikipedia.org/api/rest_v1/page/summary/' . urlencode($pays);
            $response = $this->httpClient->request('GET', $url);
            $data     = $response->toArray();

            if (isset($data['extract']) && is_string($data['extract'])) {
                $description = $data['extract'];
                if (strlen($description) > 400) {
                    $description = substr($description, 0, 400) . '...';
                }
                return $description;
            }

            return "Découvrez les merveilles de $pays, une destination d'exception pour vos prochains voyages.";
        } catch (\Exception $e) {
            return "Découvrez les merveilles de $pays, une destination d'exception pour vos prochains voyages.";
        }
    }

    // ==================== PAGE DETAIL D'UN VOYAGE ====================

    #[Route('/voyage/{id}', name: 'app_voyage_show')]
    public function show(int $id, VoyageRepository $voyageRepo, PlanjournalierRepository $planRepo): Response
    {
        $voyage = $voyageRepo->find($id);
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage non trouvé');
        }

        // ✅ Fix: $voyage est maintenant typé Voyage, getDestination() est accessible
        $planJournaliers = $planRepo->findPlanJournalierWithActiviteByVoyageId($id);
        $pointsCarte     = $planRepo->findPointsCarteByVoyageId($id);
        $galleryImages   = $this->getUnsplashImages($voyage->getDestination() ?? '', 12);

        return $this->render('voyage/detail-voyage.html.twig', [
            'voyage'          => $voyage,
            'planJournaliers' => $planJournaliers,
            'galleryImages'   => $galleryImages,
            'pointsCarte'     => $pointsCarte,
        ]);
    }

    // ==================== FONCTION : TAUX DE CHANGE ====================

    /** @return array<string, float|int> */
    private function getTauxChange(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.frankfurter.app/latest?from=TND&to=EUR,USD,GBP');
            $data     = $response->toArray();

            return [
                'tnd' => 1,
                'eur' => isset($data['rates']['EUR']) ? (float) $data['rates']['EUR'] : 0.30,
                'usd' => isset($data['rates']['USD']) ? (float) $data['rates']['USD'] : 0.32,
                'gbp' => isset($data['rates']['GBP']) ? (float) $data['rates']['GBP'] : 0.26,
            ];
        } catch (\Exception $e) {
            return ['tnd' => 1, 'eur' => 0.30, 'usd' => 0.32, 'gbp' => 0.26];
        }
    }

    // ==================== API METEO ====================

    /** @return array<string, mixed> */
    private function getMeteo(string $pays): array
    {
        $apiKey = $_ENV['OPENWEATHER_API_KEY'] ?? '';

        if (empty($apiKey)) {
            return $this->getDefaultMeteo();
        }

        try {
            $url      = "https://api.openweathermap.org/data/2.5/weather?q={$pays}&units=metric&lang=fr&appid={$apiKey}";
            $response = $this->httpClient->request('GET', $url);
            $data     = $response->toArray();

            if (isset($data['main']) && is_array($data['main'])) {
                $temp        = (int) round((float) $data['main']['temp']);
                $feel        = (int) round((float) $data['main']['feels_like']);
                $hum         = (int) $data['main']['humidity'];
                $wind        = (int) round((float) $data['wind']['speed']);
                $description = isset($data['weather'][0]['description']) ? (string) $data['weather'][0]['description'] : '';
                $iconCode    = isset($data['weather'][0]['icon']) ? (string) $data['weather'][0]['icon'] : '';

                return [
                    'temp'    => $temp,
                    'feel'    => $feel,
                    'hum'     => $hum,
                    'wind'    => $wind,
                    'uv'      => '--',
                    'vis'     => '--',
                    'icon'    => $this->getWeatherIcon($iconCode),
                    'desc'    => ucfirst($description),
                    'subdesc' => $this->getWeatherSubdesc((float) $temp, $description),
                    'ville'   => isset($data['name']) ? (string) $data['name'] : $pays,
                ];
            }

            return $this->getDefaultMeteo();
        } catch (\Exception $e) {
            return $this->getDefaultMeteo();
        }
    }

    private function getWeatherIcon(string $iconCode): string
    {
        $icons = [
            '01d' => '☀️', '01n' => '🌙',
            '02d' => '⛅',  '02n' => '☁️',
            '03d' => '☁️', '03n' => '☁️',
            '04d' => '☁️', '04n' => '☁️',
            '09d' => '🌧️', '09n' => '🌧️',
            '10d' => '🌦️', '10n' => '🌧️',
            '11d' => '⛈️', '11n' => '⛈️',
            '13d' => '❄️', '13n' => '❄️',
            '50d' => '🌫️', '50n' => '🌫️',
        ];
        return $icons[$iconCode] ?? '🌍';
    }

    private function getWeatherSubdesc(float $temp, string $description): string
    {
        if ($temp > 28) return 'Chaleur intense, idéal pour les activités en extérieur.';
        if ($temp > 22) return 'Douceur agréable, parfait pour visiter.';
        if ($temp > 15) return 'Température agréable.';
        if ($temp > 8)  return 'Frais, prévoyez une veste légère.';
        return 'Temps froid, couvrez-vous bien.';
    }

    /** @return array<string, mixed> */
    private function getDefaultMeteo(): array
    {
        return [
            'temp'    => '--',
            'feel'    => '--',
            'hum'     => '--',
            'wind'    => '--',
            'uv'      => '--',
            'vis'     => '--',
            'icon'    => '🌍',
            'desc'    => 'Données météo temporairement indisponibles',
            'subdesc' => 'Revenez dans quelques instants',
            'ville'   => '--',
        ];
    }

    // ==================== API MAP POUR PAYS ====================

    /** @return array<string, float|int> */
    private function getCoordinates(string $pays): array
    {
        try {
            $url      = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($pays) . '&format=json&limit=1';
            $response = $this->httpClient->request('GET', $url, ['headers' => ['User-Agent' => 'DouraMondo/1.0']]);
            $data     = $response->toArray();

            if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                return [
                    'lat'  => floatval($data[0]['lat']),
                    'lng'  => floatval($data[0]['lon']),
                    'zoom' => 5,
                ];
            }

            return ['lat' => 20, 'lng' => 0, 'zoom' => 2];
        } catch (\Exception $e) {
            return ['lat' => 20, 'lng' => 0, 'zoom' => 2];
        }
    }

    // ==================== API GALERIE ====================

    /** @return array<int, array<string, string>> */
    private function getUnsplashImages(string $destination, int $count = 6): array
    {
        $apiKey = $_ENV['UNSPLASH_ACCESS_KEY'] ?? '';

        if (empty($apiKey)) {
            return $this->getFallbackImages($destination, $count);
        }

        try {
            $url      = 'https://api.unsplash.com/search/photos?query=' . urlencode($destination) . '&per_page=' . $count . '&orientation=landscape';
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['Authorization' => 'Client-ID ' . $apiKey],
            ]);
            $data = $response->toArray();

            $images = [];
            if (isset($data['results']) && is_array($data['results']) && count($data['results']) > 0) {
                foreach ($data['results'] as $photo) {
                    if (!is_array($photo)) {
                        continue;
                    }
                    $images[] = [
                        'url'          => (string) ($photo['urls']['regular'] ?? ''),
                        'thumb'        => (string) ($photo['urls']['small'] ?? ''),
                        'alt'          => (string) ($photo['alt_description'] ?? $destination),
                        'photographer' => (string) ($photo['user']['name'] ?? ''),
                        'unsplash_url' => (string) ($photo['links']['html'] ?? ''),
                    ];
                }
                return $images;
            }

            return $this->getFallbackImages($destination, $count);
        } catch (\Exception $e) {
            return $this->getFallbackImages($destination, $count);
        }
    }

    /** @return array<int, array<string, string>> */
    private function getFallbackImages(string $destination, int $count): array
    {
        $images   = [];
        $keywords = [$destination, 'travel', 'nature', 'city', 'landmark', 'culture'];
        for ($i = 0; $i < $count; $i++) {
            $keyword  = $keywords[$i % count($keywords)];
            $images[] = [
                'url'          => "https://source.unsplash.com/featured/600x800/?{$keyword}",
                'thumb'        => "https://source.unsplash.com/featured/300x400/?{$keyword}",
                'alt'          => $keyword,
                'photographer' => 'Unsplash',
                'unsplash_url' => "https://unsplash.com/s/photos/{$keyword}",
            ];
        }
        return $images;
    }

    // ==================== ADMIN LISTE DE VOYAGES ====================

    #[Route('/admin/voyages', name: 'app_voyage_admin')]
    public function voyages(VoyageRepository $repo, SessionInterface $session): Response
    {
        if (!$session->get('user_id') || $session->get('user_role') !== 'admin') {
            return $this->redirectToRoute('login');
        }

        $voyages = $repo->findAllForAdmin();
        $flags   = [];
        foreach ($voyages as $voyage) {
            /** @var Voyage $voyage */
            $flags[$voyage->getIdVoyage()] = $this->getFlagEmoji($voyage->getDestination() ?? '');
        }

        return $this->render('admin/admin-liste.html.twig', [
            'active_menu' => 'voyages',
            'voyages'     => $voyages,
            'flags'       => $flags,
        ]);
    }

    // ==================== ADMIN : FORMULAIRE AJOUTER ====================

    #[Route('/admin/voyages/ajouter', name: 'app_voyage_ajouter')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $voyage = new Voyage();
        $form   = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($voyage);
            $entityManager->flush();
            $this->addFlash('success', '✅ Voyage "' . $voyage->getTitre() . '" créé avec succès !');
            return $this->redirectToRoute('app_voyage_planifier', ['id' => $voyage->getIdVoyage()]);
        }

        return $this->render('admin/ajouter_voyage.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ==================== ADMIN : PLANIFIER LE SÉJOUR ====================

    #[Route('/admin/voyages/planifier/{id}', name: 'app_voyage_planifier')]
    public function planifier(int $id, VoyageRepository $repo, ActiviteRepository $activiteRepo): Response
    {
        $voyage = $repo->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        // ✅ Fix: $voyage est Voyage, getDestination() accessible
        $activites = $activiteRepo->findByDestination($voyage->getDestination() ?? '');

        return $this->render('admin/planifier_sejour.html.twig', [
            'active_menu' => 'voyages',
            'voyage'      => $voyage,
            'activites'   => $activites,
        ]);
    }

    // ==================== API DRAPEAUX (REST Countries) ====================

    private function extractCountryName(string $destination): string
    {
        if (str_contains($destination, ',')) {
            $parts = explode(',', $destination);
            return trim($parts[1]);
        }
        return trim($destination);
    }

    private function getCountryCodeFromApi(string $countryName): ?string
    {
        static $cache = [];
        $key = mb_strtolower(trim($countryName));

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $response = $this->httpClient->request('GET', 'https://restcountries.com/v3.1/translation/' . urlencode($key) . '?fields=cca2');
            $data     = $response->toArray();
            if (!empty($data) && isset($data[0]['cca2']) && is_string($data[0]['cca2'])) {
                $cache[$key] = strtolower($data[0]['cca2']);
                return $cache[$key];
            }
        } catch (\Exception $e) {
        }

        try {
            $response = $this->httpClient->request('GET', 'https://restcountries.com/v3.1/name/' . urlencode($key) . '?fields=cca2');
            $data     = $response->toArray();
            if (!empty($data) && isset($data[0]['cca2']) && is_string($data[0]['cca2'])) {
                $cache[$key] = strtolower($data[0]['cca2']);
                return $cache[$key];
            }
        } catch (\Exception $e) {
        }

        $cache[$key] = null;
        return null;
    }

    private function getFlagEmoji(string $destination): string
    {
        $countryName = $this->extractCountryName($destination);
        $countryCode = $this->getCountryCodeFromApi($countryName);

        if ($countryCode !== null) {
            return 'https://flagcdn.com/' . $countryCode . '.svg';
        }
        return '';
    }

    // ==================== ADMIN : SUPPRIMER UN VOYAGE ====================

    #[Route('/admin/voyages/supprimer/{id}', name: 'app_voyage_supprimer', methods: ['POST'])]
    public function supprimer(int $id, VoyageRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $voyage = $repo->find($id);

        if (!$voyage) {
            return $this->json(['success' => false, 'message' => 'Voyage non trouvé'], 404);
        }

        try {
            // ✅ Fix: $voyage est Voyage, getTitre() accessible
            $titre = $voyage->getTitre();
            $em->remove($voyage);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Voyage "' . $titre . '" supprimé avec succès']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }

    // ==================== ADMIN : MISE A JOUR UN VOYAGE ====================

    #[Route('/admin/voyages/modifier/{id}', name: 'app_voyage_modifier')]
    public function modifier(int $id, Request $request, VoyageRepository $voyageRepository, EntityManagerInterface $entityManager): Response
    {
        $voyage = $voyageRepository->find($id);
        $form   = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_voyage_admin');
        }

        return $this->render('admin/modifier_voyage.html.twig', [
            'form'   => $form->createView(),
            'voyage' => $voyage,
        ]);
    }

    // ==================== ADMIN : EXPORT PDF ====================

    #[Route('/admin/voyages/export/pdf', name: 'app_export_pdf')]
    public function exportPdf(VoyageRepository $repo, \Knp\Snappy\Pdf $snappy): Response
    {
        $voyages = $repo->findAllForAdmin();
        $html    = $this->renderView('admin/export-pdf.html.twig', [
            'voyages' => $voyages,
            'date'    => new \DateTime(),
        ]);

        $filename = 'doura-mondo-voyages-' . date('Y-m-d') . '.pdf';
        $pdf      = $snappy->getOutputFromHtml($html, [
            'page-size'   => 'A4',
            'orientation' => 'Landscape',
            'encoding'    => 'UTF-8',
        ]);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ==================== ADMIN : EXPORT CSV (Excel) ====================

    #[Route('/admin/voyages/export/csv', name: 'app_export_csv')]
    public function exportCsv(VoyageRepository $repo): Response
    {
        $voyages  = $repo->findAllForAdmin();
        $filename = 'doura-mondo-voyages-' . date('Y-m-d') . '.csv';

        $response = new StreamedResponse(function () use ($voyages): void {
            // ✅ Fix: fopen() peut retourner false — on vérifie avant d'utiliser
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['DOURA MONDO — Agence de Voyages de Luxe'], ';');
            fputcsv($handle, ['Liste des voyages exportée le ' . date('d/m/Y à H:i')], ';');
            fputcsv($handle, ['Établi par : Zeyneb Chouchene — zeyneb@douramondo.com'], ';');
            fputcsv($handle, [], ';');

            fputcsv($handle, ['#', 'Titre', 'Continent', 'Destination', 'Nb Personnes', 'Durée (jours)', 'Budget estimé (€)', 'Statut'], ';');

            $totalPersonnes = 0;
            $totalBudget    = 0.0;
            $i = 1;

            foreach ($voyages as $voyage) {
                /** @var Voyage $voyage */
                $budget = (float) $voyage->getBudgetEstime();
                fputcsv($handle, [
                    $i++,
                    $voyage->getTitre(),
                    $voyage->getContinent(),
                    $voyage->getDestination(),
                    $voyage->getNbPersonnes(),
                    $voyage->getDuree(),
                    number_format($budget, 2, ',', ' '),
                    'Actif',
                ], ';');

                $totalPersonnes += (int) $voyage->getNbPersonnes();
                $totalBudget    += $budget;
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['', 'TOTAL', '', '', $totalPersonnes, '', number_format($totalBudget, 2, ',', ' ') . ' €', count($voyages) . ' voyages'], ';');

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/chatbot', name: 'app_chatbot')]
    public function index(): Response
    {
        return $this->render('/voyage/chat.html.twig');
    }
}