<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\VerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        VerificationService $verificationService,
        SessionInterface $session
    ): Response {
        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $type  = $request->request->get('type', 'email');
            $input = trim($request->request->get('contact', ''));

            if (empty($input)) {
                $error = $type === 'email'
                    ? 'Veuillez entrer votre email.'
                    : 'Veuillez entrer votre numéro de téléphone.';
            } elseif ($type === 'email' && !filter_var($input, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email invalide.';
            } elseif ($type === 'sms' && !preg_match('/^\d{8}$/', $input)) {
                $error = 'Numéro invalide (8 chiffres requis).';
            } else {
                $user = $type === 'email'
                    ? $userRepository->findOneBy(['email' => $input])
                    : $userRepository->findOneBy(['telephone' => $input]);

                if (!$user) {
                    $error = $type === 'email'
                        ? 'Aucun compte trouvé avec cet email.'
                        : 'Aucun compte trouvé avec ce numéro.';
                } elseif ($user->getStatut() !== 'actif') {
                    $error = 'Ce compte est inactif. Contactez l\'administrateur.';
                } else {
                    if ($type === 'email') {
                        $sent = $verificationService->sendPasswordResetCode(
                            $user->getEmail(),
                            $user->getPrenom()
                        );
                    } else {
                        $phone = $user->getTelephone();
                        if (preg_match('/^\d{8}$/', $phone)) {
                            $phone = '216' . $phone;
                        }
                        $sent = $verificationService->sendPasswordResetSms(
                            $phone,
                            $user->getPrenom()
                        );
                    }

                    if ($sent) {
                        $session->set('reset_user_id', $user->getIdUser());
                        $session->set('reset_type', $type);
                        $session->set('reset_sent_at', time());
                        $session->set('reset_code_verified', false); // ← AJOUTÉ
                        return $this->redirectToRoute('password_reset');
                    } else {
                        $error = "Erreur lors de l'envoi du code. Vérifiez les logs.";
                    }
                }
            }
        }

        return $this->render('forgot_password/index.html.twig', [
            'error'   => $error,
            'success' => $success,
        ]);
    }
}