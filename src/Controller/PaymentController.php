<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\Reservation;
use App\Form\PaymentType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaymentController extends AbstractController
{
    private $httpClient;
    private $paypalClientId;
    private $paypalClientSecret;
    private $paypalBaseUrl;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->paypalClientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
        $this->paypalClientSecret = $_ENV['PAYPAL_CLIENT_SECRET'] ?? '';
        $mode = $_ENV['PAYPAL_MODE'] ?? 'sandbox';
        $this->paypalBaseUrl = $mode === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

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

        $session = $request->getSession();
        $promoCode = $session->get('promo_code_' . $id);
        $discount = $session->get('promo_discount_' . $id, 0);
        
        $originalAmount = $reservation->getMontantTotal();
        $discountedAmount = $originalAmount;
        if ($discount > 0) {
            $discountedAmount = $originalAmount * (1 - $discount / 100);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $mode = $paiement->getModePaiement();
            if ($mode === 'Carte Bancaire') {
                return $this->processCardPayment($paiement, $reservation, $em, $discountedAmount, $request);
            }
        }

        return $this->render('payment/form.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
            'paypal_client_id' => $this->paypalClientId,
            'original_amount' => $originalAmount,
            'discounted_amount' => round($discountedAmount, 2),
            'discount' => $discount,
            'promo_code_applied' => $promoCode,
        ]);
    }

    #[Route('/validate-promo', name: 'validate_promo', methods: ['POST'])]
    public function validatePromo(Request $request, ReservationRepository $reservationRepo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $reservationId = $data['reservation_id'] ?? null;
            $promoCode = trim($data['promo_code'] ?? '');

            if (!$reservationId) {
                return $this->json(['error' => 'ID réservation manquant'], 400);
            }

            if (empty($promoCode)) {
                return $this->json(['error' => 'Veuillez entrer un code promo'], 400);
            }

            $reservation = $reservationRepo->find($reservationId);
            if (!$reservation) {
                return $this->json(['error' => 'Réservation introuvable'], 404);
            }

            $discount = $this->validatePromoCodeFromFile($promoCode);

            if ($discount === false) {
                return $this->json(['error' => 'Code promo invalide ou expiré'], 400);
            }

            $session = $request->getSession();
            $session->set('promo_code_' . $reservationId, $promoCode);
            $session->set('promo_discount_' . $reservationId, $discount);
            $session->save();

            $originalAmount = $reservation->getMontantTotal();
            $discountedAmount = $originalAmount * (1 - $discount / 100);

            return $this->json([
                'success' => true,
                'discount' => $discount,
                'original_amount' => $originalAmount,
                'discounted_amount' => round($discountedAmount, 2),
                'message' => sprintf('Code promo appliqué ! Réduction de %d%%', $discount)
            ]);

        } catch (\Exception $e) {
            error_log('Erreur validation promo: ' . $e->getMessage());
            return $this->json(['error' => 'Erreur lors de la validation du code'], 500);
        }
    }

    /**
     * Valide un code promo depuis le fichier JSON (sans BDD)
     */
    private function validatePromoCodeFromFile(string $code)
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $promoFile = $projectDir . '/var/promoCodes.json';
        
        if (!file_exists($promoFile)) {
            return false;
        }
        
        $content = file_get_contents($promoFile);
        $codes = json_decode($content, true);
        
        if (!is_array($codes)) {
            return false;
        }
        
        $now = new \DateTimeImmutable();
        
        foreach ($codes as &$storedCode) {
            if ($storedCode['code'] === $code) {
                $expiresAt = \DateTimeImmutable::createFromFormat('d/m/Y', $storedCode['expires_at']);
                
                if ($expiresAt && $expiresAt > $now) {
                    if ($storedCode['current_uses'] < $storedCode['max_uses']) {
                        $storedCode['current_uses']++;
                        file_put_contents($promoFile, json_encode($codes, JSON_PRETTY_PRINT));
                        return $storedCode['discount'];
                    }
                }
            }
        }
        
        return false;
    }

    private function processCardPayment(Paiement $paiement, Reservation $reservation, EntityManagerInterface $em, float $discountedAmount, Request $request): Response
    {
        $paiement->setReservation($reservation);
        $paiement->setMontant($discountedAmount);
        $paiement->setDatePaiement(new \DateTime());
        $paiement->setReferencePaiement('CARTE-'.time().'-'.$reservation->getId());
        $paiement->setStatut('payé');
        $paiement->setTypePaiement('Complet');

        $em->persist($paiement);
        $reservation->setStatut('payé');
        $em->flush();

        $session = $request->getSession();
        $session->remove('promo_code_' . $reservation->getId());
        $session->remove('promo_discount_' . $reservation->getId());

        return $this->redirectToRoute('app_confirmation', ['id' => $reservation->getId()]);
    }

    #[Route('/create-paypal-order', name: 'create_paypal_order', methods: ['POST'])]
    public function createPayPalOrder(Request $request, ReservationRepository $reservationRepo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $reservationId = $data['reservation_id'] ?? null;
            if (!$reservationId) {
                return $this->json(['error' => 'ID réservation manquant'], 400);
            }

            $reservation = $reservationRepo->find($reservationId);
            if (!$reservation) {
                return $this->json(['error' => 'Réservation introuvable'], 404);
            }

            $session = $request->getSession();
            $discount = $session->get('promo_discount_' . $reservationId, 0);
            $amount = $reservation->getMontantTotal();
            if ($discount > 0) {
                $amount = $amount * (1 - $discount / 100);
            }

            $accessToken = $this->getPayPalAccessToken();

            $response = $this->httpClient->request('POST', $this->paypalBaseUrl . '/v2/checkout/orders', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => (string) $reservation->getId(),
                            'amount' => [
                                'currency_code' => 'EUR',
                                'value' => number_format($amount, 2, '.', ''),
                            ],
                            'description' => 'Réservation Doura Mondo #DMT' . $reservation->getId(),
                        ],
                    ],
                ],
            ]);

            $orderData = $response->toArray();
            return $this->json(['id' => $orderData['id']]);
        } catch (\Exception $e) {
            error_log('PayPal create order error: ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/capture-paypal-order', name: 'capture_paypal_order', methods: ['POST'])]
    public function capturePayPalOrder(Request $request, ReservationRepository $reservationRepo, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $orderId = $data['orderID'] ?? null;
            if (!$orderId) {
                return $this->json(['error' => 'Order ID manquant'], 400);
            }

            $accessToken = $this->getPayPalAccessToken();

            $response = $this->httpClient->request('POST', $this->paypalBaseUrl . '/v2/checkout/orders/' . $orderId . '/capture', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $captureData = $response->toArray();

            if ($captureData['status'] === 'COMPLETED') {
                $purchaseUnit = $captureData['purchase_units'][0] ?? null;
                $referenceId = $purchaseUnit['reference_id'] ?? null;
                if ($referenceId) {
                    $reservation = $reservationRepo->find((int)$referenceId);
                    if ($reservation && $reservation->getStatut() !== 'payé') {
                        $session = $request->getSession();
                        $discount = $session->get('promo_discount_' . $reservation->getId(), 0);
                        $amount = $reservation->getMontantTotal();
                        if ($discount > 0) {
                            $amount = $amount * (1 - $discount / 100);
                        }
                        
                        $paiement = new Paiement();
                        $paiement->setReservation($reservation);
                        $paiement->setMontant($amount);
                        $paiement->setDatePaiement(new \DateTime());
                        $paiement->setReferencePaiement('PAYPAL-' . $captureData['id']);
                        $paiement->setModePaiement('PayPal');
                        $paiement->setStatut('payé');
                        $paiement->setTypePaiement('Complet');
                        $em->persist($paiement);
                        $reservation->setStatut('payé');
                        $em->flush();
                        
                        $session->remove('promo_code_' . $reservation->getId());
                        $session->remove('promo_discount_' . $reservation->getId());
                    }
                }
                return $this->json(['success' => true, 'reservation_id' => $referenceId]);
            } else {
                return $this->json(['error' => 'Paiement non complété'], 400);
            }
        } catch (\Exception $e) {
            error_log('PayPal capture error: ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getPayPalAccessToken(): string
    {
        $clientId = trim($this->paypalClientId);
        $secret = trim($this->paypalClientSecret);

        if (empty($clientId) || empty($secret)) {
            throw new \Exception('Identifiants PayPal manquants. Vérifiez vos variables d\'environnement PAYPAL_CLIENT_ID et PAYPAL_CLIENT_SECRET.');
        }

        $credentials = base64_encode($clientId . ':' . $secret);

        $response = $this->httpClient->request('POST', $this->paypalBaseUrl . '/v1/oauth2/token', [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => 'grant_type=client_credentials',
        ]);

        $data = $response->toArray();
        if (!isset($data['access_token'])) {
            throw new \Exception('Token non reçu : ' . json_encode($data));
        }
        return $data['access_token'];
    }
}