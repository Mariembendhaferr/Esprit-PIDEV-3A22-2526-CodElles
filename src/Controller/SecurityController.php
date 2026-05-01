<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        // Clear any existing session first to prevent auto-login
        if ($session->isStarted()) {
            // Don't auto-redirect if we're on login page
            // Only redirect if user is trying to access dashboard
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $input    = trim($request->request->get('username', ''));
            $password = trim($request->request->get('password', ''));

            // Server-side validation (NO HTML5 only!)
            if (empty($input) || empty($password)) {
                $error = 'Veuillez remplir tous les champs.';
            } elseif (strlen($input) < 3) {
                $error = 'Identifiant trop court (minimum 3 caractères).';
            } elseif (strlen($password) < 6) {
                $error = 'Mot de passe trop court (minimum 6 caractères).';
            } else {
                $user = $userRepository->findByUsernameOrEmail($input);

                if (!$user) {
                    $error = 'Aucun compte trouvé avec cet identifiant.';
                } elseif ($user->getStatut() === 'inactif') {
                    $error = 'Votre compte est inactif. Contactez l\'administration.';
                } elseif (!password_verify($password, $user->getMotDePasse())) {
                    $error = 'Mot de passe incorrect.';
                } else {
                    // Clear session first to avoid any leftover data
                    $session->clear();
                    
                    // Set new session data
                    $session->set('user_id', $user->getIdUser());
                    $session->set('user_role', $user->getRole());
                    $session->set('user_prenom', $user->getPrenom());
                    $session->set('user_nom', $user->getNom());
                    $session->set('user_email', $user->getEmail());
                    $session->set('user_username', $user->getUsername());

                    $user->setDerniereConnexion(new \DateTime());
                    $em->flush();

                    if ($user->getRole() === 'admin') {
                        return $this->redirectToRoute('app_voyage_admin');  // → /admin/voyages
                            } else {
                                return $this->redirectToRoute('app_accueil');       // → /
                                }
                }
            }
        }

        return $this->render('security/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();
        return $this->redirectToRoute('login');
    }
}