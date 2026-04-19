<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\SentimentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class RatingController extends AbstractController
{
    #[Route('/rate/{id}', name: 'rate_user')]
    public function rateUser(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        SessionInterface $session,
        EmailService $emailService,
        SentimentService $sentimentService
    ): Response {
        $ratedUser = $userRepository->find($id);
        if (!$ratedUser) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $currentUserId = $session->get('user_id');
        if (!$currentUserId) {
            return $this->redirectToRoute('login');
        }

        $currentUser = $userRepository->find($currentUserId);
        
        // Can't rate yourself
        if ($currentUserId == $id) {
            $this->addFlash('error', 'Vous ne pouvez pas vous noter vous-même');
            return $this->redirectToRoute('community_index');
        }

        if ($request->isMethod('POST')) {
            $stars = (int) $request->request->get('stars', 0);
            $comment = trim($request->request->get('comment', ''));

            // Server-side validation
            if ($stars < 1 || $stars > 5) {
                $this->addFlash('error', 'La note doit être entre 1 et 5 étoiles');
                return $this->redirectToRoute('rate_user', ['id' => $id]);
            }

            if (strlen($comment) > 500) {
                $this->addFlash('error', 'Le commentaire ne peut pas dépasser 500 caractères');
                return $this->redirectToRoute('rate_user', ['id' => $id]);
            }

            // AI Sentiment Analysis
            $sentiment = null;
            $sentimentLabel = null;
            $sentimentEmoji = null;
            $sentimentColor = null;
            
            if (!empty($comment)) {
                $analysis = $sentimentService->analyze($comment);
                $sentiment = $analysis['sentiment'];
                $sentimentLabel = $analysis['label'];
                $sentimentEmoji = $analysis['emoji'];
                $sentimentColor = $analysis['color'];
            }

            // Check if user has already rated
            $existingRating = $em->getRepository(Rating::class)->findOneBy([
                'ratedUser' => $ratedUser,
                'raterUser' => $currentUser
            ]);

            if ($existingRating) {
                $existingRating->setStars($stars);
                if (!empty($comment)) {
                    $existingRating->setComment($comment);
                }
                // Update sentiment if you have the fields
                // $existingRating->setSentiment($sentiment);
                // $existingRating->setSentimentEmoji($sentimentEmoji);
                $em->flush();
                
                $this->addFlash('success', 'Votre évaluation a été mise à jour !');
            } else {
                $rating = new Rating();
                $rating->setRatedUser($ratedUser);
                $rating->setRaterUser($currentUser);
                $rating->setStars($stars);
                $rating->setCreatedAt(new \DateTime());
                if (!empty($comment)) {
                    $rating->setComment($comment);
                }
                // Store sentiment if you have the fields in Rating entity
                // $rating->setSentiment($sentiment);
                // $rating->setSentimentEmoji($sentimentEmoji);
                
                $em->persist($rating);
                $em->flush();
                
                // Update user's average rating
                $this->updateUserAverageRating($ratedUser, $em);
                
                $this->addFlash('success', 'Merci pour votre évaluation !');
            }

            // ========== ENVOI D'EMAIL SELON LE SCÉNARIO ==========
            
            $hasComment = !empty($comment);
            
            // 1. Envoi de l'email de remerciement avec analyse sentimentale (TO THE PERSON WHO GAVE THE RATING)
            $sentimentInfo = $hasComment ? " (Analyse IA: {$sentimentEmoji} {$sentimentLabel})" : "";
            $emailService->sendThankYouForRating(
                $currentUser->getEmail(),
                $currentUser->getPrenom(),
                $stars,
                $hasComment ? $comment . $sentimentInfo : null
            );
            
            // 2. Si la note est 5 étoiles, envoi d'un email de célébration (TO THE PERSON WHO RECEIVED 5 STARS)
            if ($stars === 5) {
                $emailService->send5StarNotification(
                    $ratedUser->getEmail(),
                    $ratedUser->getPrenom(),
                    $currentUser->getPrenom()
                );
            }
            
            // 3. Si la note est 1-2 étoiles, envoi d'un email de support (TO THE PERSON WHO RECEIVED BAD REVIEW)
            if ($stars <= 2) {
                $emailService->sendBadReviewFeedback(
                    $ratedUser->getEmail(),
                    $ratedUser->getPrenom(),
                    $stars,
                    $currentUser->getPrenom(),
                    $hasComment ? $comment : null
                );
            }
            
            // 4. Si un commentaire a été écrit, envoi d'un email de confirmation avec analyse (TO THE PERSON WHO GAVE THE RATING)
            if ($hasComment) {
                $emailService->sendRatingWithCommentResponse(
                    $currentUser->getEmail(),
                    $currentUser->getPrenom(),
                    $stars,
                    $comment . "\n\n🤖 Analyse IA du sentiment: {$sentimentEmoji} {$sentimentLabel}"
                );
            }
            
            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('rating/rate.html.twig', [
            'user' => $ratedUser,
        ]);
    }
    
    private function updateUserAverageRating($user, EntityManagerInterface $em): void
    {
        $ratings = $em->getRepository(Rating::class)->findBy(['ratedUser' => $user]);
        
        if (count($ratings) > 0) {
            $total = 0;
            foreach ($ratings as $rating) {
                $total += $rating->getStars();
            }
            $average = $total / count($ratings);
            
            // If your User entity has these fields
            if (method_exists($user, 'setAverageRating')) {
                $user->setAverageRating(round($average, 1));
            }
            if (method_exists($user, 'setTotalRatings')) {
                $user->setTotalRatings(count($ratings));
            }
            
            $em->persist($user);
            $em->flush();
        }
    }
}