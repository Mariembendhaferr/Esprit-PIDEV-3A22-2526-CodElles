<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\VerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    #[Route('/password-reset', name: 'password_reset')]
    public function index(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        VerificationService $verificationService,
        EntityManagerInterface $em
    ): Response {
        $userId = $session->get('reset_user_id');
        $type   = $session->get('reset_type', 'email');
        $sentAt = $session->get('reset_sent_at', time());

        if (!$userId) {
            return $this->redirectToRoute('forgot_password');
        }

        $user = $userRepository->find($userId);
        
        if (!$user) {
            $session->remove('reset_user_id');
            return $this->redirectToRoute('forgot_password');
        }

        $error        = null;
        $success      = false;
        $codeVerified = $session->get('reset_code_verified', false);
        $expiresIn    = max(0, 900 - (time() - $sentAt));

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'resend') {
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
                    $sent = $verificationService->sendPasswordResetSms($phone, $user->getPrenom());
                }
                
                if ($sent) {
                    $session->set('reset_sent_at', time());
                    $session->set('reset_code_verified', false);
                    $codeVerified = false;
                    $expiresIn    = 900;
                    $error        = 'success:Nouveau code envoyé !';
                } else {
                    $error = "Erreur lors de l'envoi du code.";
                }

            } elseif ($action === 'verify') {
                $code = '';
                for ($i = 1; $i <= 6; $i++) {
                    $code .= $request->request->get('code' . $i, '');
                }

                if (strlen($code) !== 6) {
                    $error = 'Veuillez entrer le code complet.';
                } elseif ($verificationService->verifyPasswordResetCode($user->getIdUser(), $code)) {
                    $session->set('reset_code_verified', true);
                    $codeVerified = true;
                    $error        = 'success:Code vérifié ! Entrez votre nouveau mot de passe.';
                } else {
                    $error = 'Code incorrect ou expiré.';
                }

            } elseif ($action === 'reset' && $codeVerified) {
                $newPassword     = $request->request->get('new_password', '');
                $confirmPassword = $request->request->get('confirm_password', '');

                if (empty($newPassword)) {
                    $error = 'Veuillez entrer un nouveau mot de passe.';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Au moins 6 caractères requis.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Les mots de passe ne correspondent pas.';
                } else {
                    $user->setMotDePasse(password_hash($newPassword, PASSWORD_BCRYPT));
                    $em->flush();

                    $session->remove('reset_user_id');
                    $session->remove('reset_type');
                    $session->remove('reset_sent_at');
                    $session->remove('reset_code_verified');

                    $success = true;
                }
            }
        }

        $contact = $type === 'email'
            ? $user->getEmail()
            : '+216' . $user->getTelephone();

        return $this->render('password_reset/index.html.twig', [
            'contact'      => $contact,
            'type'         => $type,
            'error'        => $error,
            'success'      => $success,
            'codeVerified' => $codeVerified,
            'expiresIn'    => $expiresIn,
        ]);
    }
}