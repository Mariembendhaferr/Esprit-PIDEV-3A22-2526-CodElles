<?php
// src/Controller/UserManagementController.php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Exercise\HTMLPurifierBundle\HTMLPurifiersRegistryInterface;

#[Route('/admin/users')]
class UserManagementController extends AbstractController
{
    private function requireAdmin(SessionInterface $session): bool
    {
        return $session->get('user_role') === 'admin';
    }

       #[Route('/', name: 'admin_users')]
    public function index(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        $search       = $request->query->get('search', '');
        $searchBy     = $request->query->get('search_by', 'nom');
        $filterStatut = $request->query->get('statut', 'tous');
        $filterRole   = $request->query->get('role', 'tous');
        $isAjax       = $request->isXmlHttpRequest();

        $users = $userRepository->searchUsers($search, $searchBy, $filterStatut, $filterRole);

        // If AJAX request, return only the table body HTML fragment
        if ($isAjax) {
            return $this->render('admin/_user_table_body.html.twig', [
                'users' => $users,
            ]);
        }

        return $this->render('admin/users.html.twig', [
            'users'        => $users,
            'search'       => $search,
            'searchBy'     => $searchBy,
            'filterStatut' => $filterStatut,
            'filterRole'   => $filterRole,
            'totalCount'   => count($users),
        ]);
    }

    #[Route('/add', name: 'admin_user_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        HTMLPurifiersRegistryInterface $purifier
    ): Response {
        if (!$this->requireAdmin($session)) {
            return $this->redirectToRoute('login');
        }

        $errors = [];
        $old = [];
        $duplicateWarning = null;

        if ($request->isMethod('POST')) {
            $nom      = trim($request->request->get('nom', ''));
            $prenom   = trim($request->request->get('prenom', ''));
            $username = trim($request->request->get('username', ''));
            $email    = trim($request->request->get('email', ''));
            $phone    = trim($request->request->get('telephone', ''));
            $role     = $request->request->get('role', 'voyageur');
            $statut   = $request->request->get('statut', 'actif');
            $password = $request->request->get('password', '');

            // 👇 PURIFY TEXT INPUTS (Security!)
            $nom      = $purifier->get('default')->purify($nom);
            $prenom   = $purifier->get('default')->purify($prenom);
            $username = $purifier->get('default')->purify($username);

            $old = compact('nom', 'prenom', 'username', 'email', 'phone', 'role', 'statut');

            if (empty($nom) || !preg_match('/^[\p{L}\s]+$/u', $nom)) {
                $errors['nom'] = 'Nom invalide (lettres uniquement)';
            }
            if (empty($prenom) || !preg_match('/^[\p{L}\s]+$/u', $prenom)) {
                $errors['prenom'] = 'Prénom invalide (lettres uniquement)';
            }
            if (empty($username) || !preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
                $errors['username'] = 'Username invalide (lettres, chiffres, - ou _)';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format email invalide';
            }
            if (!empty($phone) && !preg_match('/^\d{8}$/', $phone)) {
                $errors['telephone'] = 'Téléphone : 8 chiffres requis';
            }
            if (strlen($password) < 6) {
                $errors['password'] = 'Mot de passe : minimum 6 caractères';
            }

          
            if (empty($errors)) {
                // Check email duplicate
                $existingEmail = $userRepository->findOneBy(['email' => $email]);
                if ($existingEmail) {
                    $duplicateWarning = "⚠️ L'email '<strong>{$email}</strong>' est déjà utilisé par <strong>{$existingEmail->getPrenom()} {$existingEmail->getNom()}</strong>";
                }

                // Check username duplicate
                $existingUsername = $userRepository->findOneBy(['username' => $username]);
                if ($existingUsername) {
                    if ($duplicateWarning) {
                        $duplicateWarning .= "<br>⚠️ Le username '<strong>{$username}</strong>' est déjà utilisé par <strong>{$existingUsername->getPrenom()} {$existingUsername->getNom()}</strong>";
                    } else {
                        $duplicateWarning = "⚠️ Le username '<strong>{$username}</strong>' est déjà utilisé par <strong>{$existingUsername->getPrenom()} {$existingUsername->getNom()}</strong>";
                    }
                }

                // If duplicates found, STOP and show warning (NO INSERT!)
                if ($duplicateWarning) {
                    return $this->render('admin/user_form.html.twig', [
                        'user'             => null,
                        'errors'           => $errors,
                        'old'              => $old,
                        'isEdit'           => false,
                        'duplicateWarning' => $duplicateWarning,
                    ]);
                }
            }

           
            if (empty($errors) && !$duplicateWarning) {
                $user = new User();
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setTelephone($phone ?: null);
                $user->setRole(in_array($role, ['admin', 'voyageur']) ? $role : 'voyageur');
                $user->setStatut(in_array($statut, ['actif', 'inactif']) ? $statut : 'actif');
                $user->setMotDePasse(password_hash($password, PASSWORD_BCRYPT));
                $user->setDateInscription(new \DateTime());
                $user->setFirstLogin(true);
                $user->setPhotoProfil('default.jpg');

                // Photo upload
                $photo = $request->files->get('photo');
                if ($photo && $photo->isValid()) {
                    $filename  = 'user-' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $photo->move($uploadDir, $filename);
                    $user->setPhotoProfil('uploads/photos/' . $filename);
                }

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Utilisateur ajouté avec succès !');
                return $this->redirectToRoute('admin_users');
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'user'             => null,
            'errors'           => $errors,
            'old'              => $old,
            'isEdit'           => false,
            'duplicateWarning' => $duplicateWarning,
        ]);
    }

          #[Route('/edit/{id}', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->requireAdmin($session)) {
            return $this->redirectToRoute('login');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($request->isMethod('POST')) {
            // 👇 ONLY READ role and statut from POST - IGNORE ALL OTHER FIELDS
            $role   = $request->request->get('role', $user->getRole());
            $statut = $request->request->get('statut', $user->getStatut());

            // Validate
            if (!in_array($role, ['admin', 'voyageur'])) {
                $this->addFlash('error', 'Rôle invalide.');
                return $this->redirectToRoute('admin_users');
            }
            if (!in_array($statut, ['actif', 'inactif'])) {
                $this->addFlash('error', 'Statut invalide.');
                return $this->redirectToRoute('admin_users');
            }

            // 👇 ONLY UPDATE ROLE AND STATUT - NOTHING ELSE!
            $user->setRole($role);
            $user->setStatut($statut);

            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès !');
            return $this->redirectToRoute('admin_users');
        }

        // 👇 Render the EXISTING template with a flag to hide/edit-only fields
        return $this->render('admin/user_form.html.twig', [
            'user'        => $user,
            'errors'      => [],
            'old'         => [],
            'isEdit'      => true,
            'limitedEdit' => true, // 👈 Tells template to show read-only fields
        ]);
    }
    #[Route('/delete/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(
        int $id,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->requireAdmin($session)) {
            return $this->redirectToRoute('login');
        }

        $user = $userRepository->find($id);
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Utilisateur non trouvé');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/view/{id}', name: 'admin_user_view')]
    public function view(
        int $id,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        if (!$this->requireAdmin($session)) {
            return $this->redirectToRoute('login');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/user_view.html.twig', [
            'user' => $user,
        ]);
    }
}