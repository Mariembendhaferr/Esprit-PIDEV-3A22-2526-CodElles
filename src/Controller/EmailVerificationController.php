<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\VerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'email_verification')]
    public function index(
        Request $request,
        SessionInterface $session,
        VerificationService $verificationService,
        EmailService $emailService,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        // 1. Get email from Query Parameter or Session
        $email = $request->query->get('email');
        
        if (!$email) {
            $pendingUser = $session->get('pending_user');
            if ($pendingUser) {
                $email = $pendingUser['email'];
            }
        }

        if (!$email) {
            return $this->redirectToRoute('register');
        }

        // 2. Fetch the user from Database
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $session->remove('pending_user');
            return $this->redirectToRoute('register');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            // --- ACTION: RESEND CODE ---
            if ($action === 'resend') {
                if ($user->getEmailVerified()) {
                    $error = 'Cet email est déjà vérifié. Veuillez vous connecter.';
                } else {
                    $sent = $verificationService->sendEmailVerificationCode($user->getEmail(), $user->getPrenom());
                    $error = $sent ? 'success:Nouveau code envoyé à ' . $user->getEmail() : "Erreur lors de l'envoi du code.";
                }
            } 
            // --- ACTION: VERIFY CODE ---
            else {
                // Collect 6-digit code
                $code = '';
                for ($i = 1; $i <= 6; $i++) {
                    $code .= $request->request->get('code' . $i, '');
                }

                if (strlen($code) !== 6) {
                    $error = 'Veuillez entrer le code complet (6 chiffres).';
                } elseif ($user->getEmailVerified()) {
                    $error = 'Cet email est déjà vérifié.';
                } elseif ($verificationService->verifyEmailCode($email, $code)) {
                    // ✅ CODE IS VALID - SUCCESS!
                    
                    error_log("✅ Verification successful for user: {$user->getIdUser()}");
                    
                    // Mark user as verified
                    $user->setEmailVerified(true);
                    $user->setVerificationCode(null);
                    $user->setVerificationCodeExpires(null);
                    $em->flush();

                    // Send Welcome Email
                    $emailService->sendWelcomeEmail($user->getEmail(), $user->getPrenom());

                    // 👇 SET SESSION DATA (CRITICAL - This is what you're missing!)
                    $session->set('user_id', $user->getIdUser());
                    $session->set('user_email', $user->getEmail());
                    $session->set('user_role', $user->getRole());
                    $session->set('user_prenom', $user->getPrenom());
                    $session->set('user_nom', $user->getNom());
                    
                    error_log("📦 Session set: user_id={$user->getIdUser()}, role={$user->getRole()}");

                    // Clean up pending data
                    $session->remove('pending_user');
                    $session->remove('verification_sent_at');

                    // 👇 DETERMINE REDIRECT BASED ON ROLE
                    $role = $user->getRole();
                    error_log("🎯 User role: {$role}");
                    
                    if ($role === 'admin') {
                        error_log("🎯 Redirecting to admin_dashboard");
                        return $this->redirectToRoute('admin_dashboard');
                    } else {
                        error_log("🎯 Redirecting to user_dashboard");
                        return $this->redirectToRoute('user_dashboard');
                    }
                    
                } else {
                    error_log("❌ Verification failed for code: {$code}");
                    $error = 'Code incorrect ou expiré.';
                }
            }
        }

        return $this->render('email_verification/index.html.twig', [
            'email'     => $email,
            'error'     => $error,
            'expiresIn' => 900,
            'user'      => $user,
        ]);
    }
}