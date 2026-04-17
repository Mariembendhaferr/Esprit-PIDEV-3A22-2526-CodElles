<?php

namespace App\Controller;

use App\Entity\Voyage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PartageController extends AbstractController
{
    public function __construct(private string $ngrokUrl = '') {}

    #[Route('/api/partage/{id}', name: 'app_partage_api', methods: ['GET'])]
    public function apiPartage(Voyage $voyage, Request $request): Response
    {
        try {
            $ngrokUrl = $this->ngrokUrl;
            
            if (empty($ngrokUrl)) {
                $ngrokUrl = $_ENV['NGROK_URL'] ?? getenv('NGROK_URL') ?? '';
            }

            $urlVoyage = $this->generateUrl('app_voyage_show', [
                'id' => $voyage->getIdVoyage()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            if (!empty($ngrokUrl)) {
                $parsedUrl = parse_url($urlVoyage);
                $urlAbsolue = rtrim($ngrokUrl, '/') . ($parsedUrl['path'] ?? '');
            } else {
                $urlAbsolue = $urlVoyage;
            }

            if (empty($urlAbsolue)) {
                $urlAbsolue = $request->getSchemeAndHttpHost() . $this->generateUrl('app_voyage_show', ['id' => $voyage->getIdVoyage()]);
            }

            $qrCodeDataUri = $this->generateQrCodeDataUri($urlAbsolue);

            return $this->json([
                'success'          => true,
                'id'               => $voyage->getIdVoyage(),
                'titre'            => $voyage->getTitre(),
                'destination'      => $voyage->getDestination(),
                'continent'        => $voyage->getContinent(),
                'prix'             => $voyage->getBudgetEstime(),
                'duree'            => $voyage->getDuree(),
                'image'            => $voyage->getImageUrl(),
                'description'      => $voyage->getDescription(),
                'url'              => $urlAbsolue,
                'qrCodeDataUri'    => $qrCodeDataUri,
                'facebookShareUrl' => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($urlAbsolue),
                'whatsappShareUrl' => "https://wa.me/?text=" . urlencode("🌟 " . $voyage->getTitre() . " - " . $urlAbsolue),
                'twitterShareUrl'  => "https://twitter.com/intent/tweet?text=" . urlencode($voyage->getTitre()) . "&url=" . urlencode($urlAbsolue),
                'linkedinShareUrl' => "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode($urlAbsolue),
                'mailSubject'      => "🌟 Voyage exceptionnel : " . $voyage->getTitre(),
                'mailBody'         => "Bonjour,\n\nJe voulais partager ce voyage exceptionnel avec vous :\n\n"
                                    . $voyage->getTitre() . "\n"
                                    . $voyage->getDestination() . ", " . $voyage->getContinent() . "\n"
                                    . "Prix à partir de " . number_format($voyage->getBudgetEstime(), 0, ',', ' ') . " DT\n\n"
                                    . $urlAbsolue . "\n\nBonne découverte !"
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateQrCodeDataUri(string $url): string
    {
        try {
            
            // Essayer d'abord avec Endroid QR Code
            $builder = new Builder(
                writer: new PngWriter(),
                data: $url,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 200,
                margin: 10
            );
            
            $result = $builder->build();
            $imageData = base64_encode($result->getString());
            
            return 'data:image/png;base64,' . $imageData;
            
        } catch (\Exception $e) {
            // Si Endroid échoue, utiliser l'API externe gratuite comme fallback
            try {
                $qrApiUrl = "https://quickchart.io/qr?text=" . urlencode($url) . "&size=200&margin=2";
                $qrImageData = @file_get_contents($qrApiUrl);
                
                if ($qrImageData !== false) {
                    return 'data:image/png;base64,' . base64_encode($qrImageData);
                }
            } catch (\Exception $e2) {
                // Ignorer l'erreur
            }
            
            return '';
        }
    }
}