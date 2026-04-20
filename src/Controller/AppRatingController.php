<?php

namespace App\Controller;

use App\Entity\AppRating;
use App\Repository\AppRatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppRatingController extends AbstractController
{
    #[Route('/app-rating', name: 'app_rating')]
    public function index(
        Request $request,
        AppRatingRepository $appRatingRepository,
        EntityManagerInterface $em
    ): Response {
        // Get current user from session (we'll set this up later)
        $currentUser = $this->getUser();

        $alreadyRated = false;
        $error        = null;
        $success      = false;

        if ($currentUser) {
            $alreadyRated = $appRatingRepository->hasUserRated($currentUser->getIdUser());
        }

        if ($request->isMethod('POST') && $currentUser) {
            if ($alreadyRated) {
                $error = "Vous avez déjà évalué l'application.";
            } else {
                $rating  = (int) $request->request->get('rating', 50);
                $comment = trim($request->request->get('comment', ''));

                // Validate server-side only
                if ($rating < 0 || $rating > 100) {
                    $error = "La note doit être entre 0 et 100.";
                } else {
                    $appRating = new AppRating();
                    $appRating->setUser($currentUser);
                    $appRating->setRating($rating);
                    $appRating->setComment($comment ?: null);
                    $appRating->setCreatedAt(new \DateTime());

                    $em->persist($appRating);
                    $em->flush();

                    $success = true;
                    $alreadyRated = true;
                }
            }
        }

        return $this->render('app_rating/index.html.twig', [
            'currentUser'  => $currentUser,
            'alreadyRated' => $alreadyRated,
            'error'        => $error,
            'success'      => $success,
        ]);
    }
}