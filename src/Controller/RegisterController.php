<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\VerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'register')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        VerificationService $verificationService,
        SessionInterface $session,
        EntityManagerInterface $em
    ): Response {
        $errors = [];
        $old = [];

        if ($request->isMethod('POST')) {
            $nom       = trim($request->request->get('nom', ''));
            $prenom    = trim($request->request->get('prenom', ''));
            $username  = trim($request->request->get('username', ''));
            $email     = trim($request->request->get('email', ''));
            $phone     = trim($request->request->get('telephone', ''));
            $password  = $request->request->get('password', '');
            $captchaOk = $request->request->get('captcha_drag_verified', '0');
            $avatar    = $request->request->get('selected_avatar', '');
            
            $old = compact('nom', 'prenom', 'username', 'email', 'phone');

            // --- VALIDATION ---
            if (empty($nom) || !preg_match('/^[\p{L}\s]+$/u', $nom)) {
                $errors['nom'] = 'Nom invalide (lettres uniquement)';
            }
            if (empty($prenom) || !preg_match('/^[\p{L}\s]+$/u', $prenom)) {
                $errors['prenom'] = 'Prénom invalide (lettres uniquement)';
            }
            if (empty($username) || strlen($username) < 3) {
                $errors['username'] = 'Pseudo trop court (min 3 caractères)';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Adresse email invalide';
            }
            if (!empty($phone) && !preg_match('/^\d{8}$/', $phone)) {
                $errors['telephone'] = 'Téléphone : 8 chiffres requis';
            }
            if (strlen($password) < 6) {
                $errors['password'] = 'Mot de passe : minimum 6 caractères';
            }

            // Captcha validation
            if ($captchaOk !== '1') {
                $errors['captcha'] = 'Veuillez glisser l\'avion vers la destination.';
            }

            // --- DUPLICATE CHECK (FIXED LOGIC) ---
            if (empty($errors)) {
                // 1. Check Username (Always block if taken)
                if ($userRepository->findOneBy(['username' => $username])) {
                    $errors['username'] = 'Ce nom d\'utilisateur est déjà pris';
                }

                // 2. Check Email (Special Logic)
                $existingUser = $userRepository->findOneBy(['email' => $email]);
                
                if ($existingUser) {
                    // ✅ CASE A: User exists but is NOT verified -> Allow re-registration (Resend Code)
                    if (!$existingUser->isEmailVerified()) {
                        // We don't add an error here. Instead, we will handle the resend below.
                        // But we need to stop the "New User Creation" flow and go to "Resend" flow.
                        
                        // Generate NEW code for this existing user
                        $newCode = random_int(100000, 999999);
                        $existingUser->setVerificationCode($newCode);
                        $existingUser->setVerificationCodeExpires(new \DateTime('+15 minutes'));
                        $em->flush();

                        // Send new email
                        $verificationService->sendEmailVerificationCode($email, $existingUser->getPrenom(), $newCode);

                        // Redirect to verification page immediately with success message
                        $this->addFlash('success', 'Un nouveau code a été envoyé à votre email.');
                        return $this->redirectToRoute('email_verification', ['email' => $email]);
                    } 
                    
                    // ❌ CASE B: User exists AND is verified -> Block registration
                    else {
                        $errors['email'] = 'Cet email est déjà utilisé et vérifié.';
                    }
                }
            }

            // --- CREATE NEW USER (Only if no errors and not a resend case) ---
            if (empty($errors)) {
                // Photo upload
                $photoPath = null;
                $photo = $request->files->get('photo');
                if ($photo && $photo->isValid()) {
                    $filename = uniqid() . '.' . $photo->guessExtension();
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $photo->move($uploadDir, $filename);
                    $photoPath = 'uploads/photos/' . $filename;
                }

                // Save pending data to session (optional, but good for multi-step)
                // Or just create the user directly since validation passed
                $user = new User();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setTelephone($phone ?: null);
                $user->setMotDePasse(password_hash($password, PASSWORD_BCRYPT));
                $user->setRole('voyageur');
                $user->setStatut('actif');
                $user->setDateInscription(new \DateTime());
                $user->setFirstLogin(true);
                $user->setPhotoProfil($photoPath ?: ($avatar ? 'adventurer/'.$avatar : 'default.jpg'));
                $user->setEmailVerified(false);

                // Generate Initial Code
                $code = random_int(100000, 999999);
                $user->setVerificationCode($code);
                $user->setVerificationCodeExpires(new \DateTime('+15 minutes'));

                $em->persist($user);
                $em->flush();

                // Send Email
                $sent = $verificationService->sendEmailVerificationCode($email, $prenom, $code);

                if ($sent) {
                    return $this->redirectToRoute('email_verification', ['email' => $email]);
                } else {
                    $errors['general'] = "Erreur d'envoi de l'email de vérification.";
                    // Rollback user creation if email fails? Optional.
                }
            }
        }

        $avatars = [
            'adventurer', 'avataaars', 'big-ears', 'big-smile', 
            'bottts', 'croodles', 'fun-emoji', 'icons', 
            'identicon', 'lorelei', 'micah', 'miniavs', 
            'open-peeps', 'personas', 'pixel-art'
        ];

        return $this->render('register/index.html.twig', [
            'errors'  => $errors,
            'old'     => $old,
            'avatars' => $avatars,
        ]);
    }
}