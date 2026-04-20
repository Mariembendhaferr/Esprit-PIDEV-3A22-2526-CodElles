<?php
// src/Controller/QrCodeController.php
namespace App\Controller;

use App\Repository\UserRepository;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QrCodeController extends AbstractController
{
    #[Route('/qr/{username}', name: 'qr_code_show', methods: ['GET'])]
    public function show(string $username, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['username' => $username]);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        // Generate the public profile URL
        $profileUrl = $this->generateUrl('public_profile', [
            'username' => $user->getUsername()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // Build QR Code using CONSTRUCTOR (v6 API)
        $qrCode = (new QrCode($profileUrl))
            ->setSize(300)
            ->setMargin(10)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }
    
    #[Route('/user/qr-code', name: 'user_qr_code')]
    public function userQrCode(
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        // Get user from session (your custom auth)
        $userId = $session->get('user_id');
        
        if (!$userId) {
            return $this->redirectToRoute('login');
        }
        
        $user = $userRepository->find($userId);
        
        if (!$user) {
            $session->invalidate();
            return $this->redirectToRoute('login');
        }
        
        return $this->render('user/qr_code.html.twig', [
            'user' => $user,
        ]);
    }
}