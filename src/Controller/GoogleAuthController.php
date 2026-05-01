<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class GoogleAuthController extends AbstractController
{
    #[Route('/auth/google', name: 'google_auth')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        // Redirige vers Google pour l'authentification
        return $clientRegistry->getClient('google')->redirect([
            'email', 
            'profile'
        ]);
    }

    #[Route('/auth/google/callback', name: 'google_callback')]
    public function callback(
        Request $request,
        ClientRegistry $clientRegistry,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        SessionInterface $session,
        LoggerInterface $logger
    ): Response {
        try {
            // Log pour debug
            $logger->info('Tentative de connexion Google');
            
            $client = $clientRegistry->getClient('google');
            
            // Récupère l'utilisateur Google
            $googleUser = $client->fetchUser();
            
            $logger->info('Utilisateur Google récupéré', [
                'email' => $googleUser->getEmail(),
                'firstName' => $googleUser->getFirstName(),
                'lastName' => $googleUser->getLastName()
            ]);
            
            $email = $googleUser->getEmail();
            
            if (empty($email)) {
                $logger->warning('Email vide, utilisation de l\'ID Google');
                $email = $googleUser->getId() . '@google.user';
            }
            
            // Vérifier si l'utilisateur existe déjà
            $user = $userRepository->findOneBy(['email' => $email]);
            
            if (!$user) {
                $logger->info('Création d\'un nouvel utilisateur Google');
                
                // Vérifier si le username existe déjà
                $baseUsername = explode('@', $email)[0];
                $username = $baseUsername;
                $counter = 1;
                
                while ($userRepository->findOneBy(['username' => $username])) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                
                $user = new User();
                $user->setNom($googleUser->getLastName() ?? '');
                $user->setPrenom($googleUser->getFirstName() ?? '');
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setMotDePasse(''); // Pas de mot de passe pour les comptes Google
                $user->setRole('voyageur');
                $user->setStatut('actif');
                $user->setDateInscription(new \DateTime());
                $user->setFirstLogin(true);
                $user->setPhotoProfil('default.jpg'); // Photo par défaut
                
                $em->persist($user);
                $em->flush();
                
                $logger->info('Utilisateur créé avec succès', ['id' => $user->getIdUser()]);
            } else {
                $logger->info('Utilisateur existant trouvé', ['id' => $user->getIdUser()]);
            }
            
            // Mettre à jour la dernière connexion
            $user->setDerniereConnexion(new \DateTime());
            $em->flush();
            
            // Connecter l'utilisateur
            $session->set('user_id', $user->getIdUser());
            $session->set('user_role', $user->getRole());
            $session->set('user_email', $user->getEmail());
            
            $logger->info('Connexion Google réussie', [
                'user_id' => $user->getIdUser(),
                'role' => $user->getRole()
            ]);
            
            // 👇 Redirect according to role
            // Admins go to admin_dashboard, regular users go to user_dashboard
            $redirectRoute = $user->getRole() === 'admin' ? 'admin_dashboard' : 'user_dashboard';
            
            return $this->redirectToRoute($redirectRoute);
            
        } catch (\Exception $e) {
            // Log détaillé de l'erreur
            $logger->error('Erreur lors de l\'authentification Google', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ajouter un message flash pour informer l'utilisateur
            $this->addFlash('error', 'Erreur lors de la connexion avec Google. Veuillez réessayer.');
            
            return $this->redirectToRoute('login');
        }
    }
}