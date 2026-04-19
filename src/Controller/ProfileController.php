<?php

namespace App\Controller;

use App\Repository\AppRatingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function index(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        AppRatingRepository $appRatingRepository,
        EntityManagerInterface $em
    ): Response {
        $userId = $session->get('user_id');
        if (!$userId) return $this->redirectToRoute('login');

        $user    = $userRepository->find($userId);
        $errors  = [];
        $success = false;

        $appAvg   = $appRatingRepository->getAverageRating();
        $appTotal = $appRatingRepository->getTotalRatings();

        if ($request->isMethod('POST')) {
            $nom      = trim($request->request->get('nom', ''));
            $prenom   = trim($request->request->get('prenom', ''));
            $username = trim($request->request->get('username', ''));
            $phone    = trim($request->request->get('telephone', ''));

            // Validation stricte pour le nom (lettres, espaces, tirets, apostrophes uniquement)
            if (strlen($nom) < 2) {
                $errors['nom'] = 'Nom trop court (min 2 caractères)';
            } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $nom)) {
                $errors['nom'] = 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes';
            }
            
            // Validation stricte pour le prénom (lettres, espaces, tirets, apostrophes uniquement)
            if (strlen($prenom) < 2) {
                $errors['prenom'] = 'Prénom trop court (min 2 caractères)';
            } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $prenom)) {
                $errors['prenom'] = 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes';
            }
            
            // Validation pour le username (lettres, chiffres, tiret, underscore)
            if (strlen($username) < 3) {
                $errors['username'] = 'Pseudo trop court (min 3 caractères)';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                $errors['username'] = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores';
            }
            
            // Validation téléphone
            if (!empty($phone) && !preg_match('/^\d{8}$/', $phone)) {
                $errors['telephone'] = 'Téléphone : 8 chiffres requis';
            }

            $newPassword = trim($request->request->get('new_password', ''));
            if (!empty($newPassword) && strlen($newPassword) < 6) {
                $errors['password'] = 'Mot de passe trop court (min 6 caractères)';
            }

            if (empty($errors)) {
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setUsername($username);
                $user->setTelephone($phone ?: null);

                if (!empty($newPassword)) {
                    $user->setMotDePasse(password_hash($newPassword, PASSWORD_BCRYPT));
                }

                // Handle photo upload
                /** @var UploadedFile|null $photo */
                $photo = $request->files->get('photo');
                if ($photo) {
                    $filename  = 'profile-' . $userId . '-' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
                    $photo->move($uploadDir, $filename);
                    $user->setPhotoProfil('uploads/photos/' . $filename);
                }

                // Admin fields
                if ($user->getRole() === 'admin') {
                    $role   = $request->request->get('role');
                    $statut = $request->request->get('statut');
                    if (in_array($role,   ['admin', 'voyageur'])) $user->setRole($role);
                    if (in_array($statut, ['actif', 'inactif']))  $user->setStatut($statut);
                }

                $em->flush();
                $success = true;
            }
        }

        return $this->render('profile/index.html.twig', [
            'user'     => $user,
            'errors'   => $errors,
            'success'  => $success,
            'appAvg'   => $appAvg,
            'appTotal' => $appTotal,
        ]);
    }

    #[Route('/profile/avatar', name: 'profile_avatar', methods: ['POST'])]
    public function updateAvatar(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $userId = $session->get('user_id');
        if (!$userId) return $this->redirectToRoute('login');

        $user   = $userRepository->find($userId);
        $avatar = $request->request->get('selected_avatar');

        if ($avatar && $user) {
            $user->setPhotoProfil($avatar);
            $em->flush();
        }

        return $this->redirectToRoute('profile');
    }
    #[Route('/profile/qr-code', name: 'qr_profile')]
public function qrCode(
    SessionInterface $session,
    UserRepository $userRepository
): Response {
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