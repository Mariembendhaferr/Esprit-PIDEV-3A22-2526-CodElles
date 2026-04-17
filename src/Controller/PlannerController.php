<?php
// src/Controller/PlannerController.php

namespace App\Controller;

use App\Service\GroqPlannerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


class PlannerController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $unsplashKey,
    ) {}

    // ── Page formulaire wizard ────────────────────────────────────────────────

    #[Route('/planifier', name: 'app_planifier', methods: ['GET'])]
    public function wizard(): Response
    {
        return $this->render('voyage/planifier.html.twig');
    }

    // ── Génération du plan via Groq ───────────────────────────────────────────

    #[Route('/planifier/generer', name: 'app_planifier_generer', methods: ['POST'])]
    public function generer(
        Request             $request,
        GroqPlannerService  $planner,
    ): Response {
        $preferences = [
            'destination'  => trim($request->request->get('destination', '')),
            'budget'       => (int) $request->request->get('budget', 1000),
            'nb_personnes' => (int) $request->request->get('nb_personnes', 2),
            'duree'        => (int) $request->request->get('duree', 5),
            'theme'        => $request->request->get('theme', 'Découverte'),
            'hebergement'  => $request->request->get('hebergement', 'Hôtel'),
            'rythme'       => $request->request->get('rythme', 'Modéré'),
            'description'  => trim($request->request->get('description', '')),
        ];

        // Validation minimale
        if (empty($preferences['destination'])) {
            return $this->redirectToRoute('app_planifier');
        }

        try {
            $plan = $planner->generatePlan($preferences);

            // Enrichir chaque activité avec une image Unsplash
            if (isset($plan['jours']) && is_array($plan['jours'])) {
                foreach ($plan['jours'] as &$jour) {
                    foreach (['matin', 'apres_midi', 'soir'] as $moment) {
                        if (isset($jour[$moment]['image_query'])) {
                            $jour[$moment]['image_url'] = $this->fetchUnsplashImage(
                                $jour[$moment]['image_query']
                            );
                        }
                    }
                }
                unset($jour);
            }

            // Stocker en session
            $request->getSession()->set('travel_plan', $plan);
            $request->getSession()->set('travel_preferences', $preferences);

            return $this->redirectToRoute('app_planifier_resultat');

        } catch (\Throwable $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la génération du plan. Veuillez réessayer.');
            return $this->redirectToRoute('app_planifier');
        }
    }

    // ── Page résultat ─────────────────────────────────────────────────────────

    #[Route('/planifier/resultat', name: 'app_planifier_resultat', methods: ['GET'])]
    public function resultat(Request $request): Response
    {
        $plan        = $request->getSession()->get('travel_plan');
        $preferences = $request->getSession()->get('travel_preferences');

        if (!$plan) {
            return $this->redirectToRoute('app_planifier');
        }

        return $this->render('voyage/plan_resultat.html.twig', [
            'plan'        => $plan,
            'preferences' => $preferences,
        ]);
    }

    // ── Fetch image Unsplash ──────────────────────────────────────────────────

    private function fetchUnsplashImage(string $query): string
    {
        $fallback = 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800&q=80';

        try {
            $response = $this->httpClient->request('GET',
                'https://api.unsplash.com/photos/random',
                [
                    'query' => [
                        'query'       => $query,
                        'orientation' => 'landscape',
                        'content_filter' => 'high',
                    ],
                    'headers' => [
                        'Authorization' => 'Client-ID ' . $this->unsplashKey,
                    ],
                    'timeout' => 8,
                ]
            );

            $data = $response->toArray();
            return $data['urls']['regular'] ?? $fallback;

        } catch (\Throwable) {
            return $fallback;
        }
    }


    // ── Envoi de la demande par mail ──────────────────────────────────────────

    #[Route('/planifier/envoyer', name: 'app_planifier_envoyer', methods: ['POST'])]
    public function envoyerDemande(
        Request         $request,
        MailerInterface $mailer,
    ): Response {
        $plan        = $request->getSession()->get('travel_plan');
        $preferences = $request->getSession()->get('travel_preferences');

        if (!$plan || !$preferences) {
            $this->addFlash('error', 'Aucun plan trouvé. Veuillez d\'abord générer un plan.');
            return $this->redirectToRoute('app_planifier');
        }

        $destination = $preferences['destination'] ?? 'Destination inconnue';

        try {
            $htmlContent = $this->renderView('voyage/demande_plan.html.twig', [
                'plan'        => $plan,
                'preferences' => $preferences,
            ]);

            $email = (new Email())
                ->from('noreply@douramondo.com')
                ->to('zeyneb.chouchene@esprit.tn')
                ->subject('🌍 Nouvelle demande de personnalisation — ' . $destination)
                ->html($htmlContent);

            $mailer->send($email);

            // Nettoyer la session après envoi
            $request->getSession()->remove('travel_plan');
            $request->getSession()->remove('travel_preferences');

            $this->addFlash('success', 'Votre demande a bien été envoyée à nos experts locaux ! Ils vous contacteront très prochainement pour finaliser votre voyage sur mesure. ✈️');

        } catch (\Throwable $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.');
            return $this->redirectToRoute('app_planifier_resultat');
        }

        return $this->redirectToRoute('app_accueil');
    }



}
