<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/booking', name: 'app_booking_')]
class BookingActiviteController extends AbstractController
{
    #[Route('/reserve/{id}', name: 'reserve', methods: ['POST'])]
    public function reserve(
        Activite $activite,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        // Get the user (for now, we'll use ID from request or authenticated user)
        $userId = $request->request->get('user_id');
        
        if (!$userId) {
            return $this->json(
                ['success' => false, 'message' => 'User not authenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(
                ['success' => false, 'message' => 'User not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Check if user already booked this activity
        if ($activite->isBookedByUser($user)) {
            return $this->json(
                ['success' => false, 'message' => 'You have already booked this activity'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Check availability
        if ($activite->isFull()) {
            return $this->json(
                ['success' => false, 'message' => 'No places available'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // Add user to activity
            $activite->addBookedByUser($user);
            
            // Increment reserved places
            $activite->incrementPlacesReserves();

            // Save to database
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Booking confirmed!',
                'places_remaining' => $activite->getPlacesDisponibles(),
                'places_reserved' => $activite->getPlacesReserves(),
                'capacity' => $activite->getCapaciteMaxActivite(),
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/cancel/{id}', name: 'cancel', methods: ['POST'])]
    public function cancel(
        Activite $activite,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        $userId = $request->request->get('user_id');
        
        if (!$userId) {
            return $this->json(
                ['success' => false, 'message' => 'User not authenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = $em->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(
                ['success' => false, 'message' => 'User not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Check if user has booked this activity
        if (!$activite->isBookedByUser($user)) {
            return $this->json(
                ['success' => false, 'message' => 'You have not booked this activity'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // Remove user from activity
            $activite->removeBookedByUser($user);
            
            // Decrement reserved places
            $activite->decrementPlacesReserves();

            // Save to database
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'Booking cancelled',
                'places_remaining' => $activite->getPlacesDisponibles(),
                'places_reserved' => $activite->getPlacesReserves(),
                'capacity' => $activite->getCapaciteMaxActivite(),
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/status/{id}', name: 'status', methods: ['GET'])]
    public function status(Activite $activite): JsonResponse
    {
        return $this->json([
            'activity_id' => $activite->getIdActivite(),
            'name' => $activite->getNomActivite(),
            'places_reserved' => $activite->getPlacesReserves(),
            'places_available' => $activite->getPlacesDisponibles(),
            'capacity' => $activite->getCapaciteMaxActivite(),
            'is_full' => $activite->isFull(),
            'percentage_booked' => $activite->getCapaciteMaxActivite() > 0 
                ? round(($activite->getPlacesReserves() / $activite->getCapaciteMaxActivite()) * 100)
                : 0,
        ]);
    }
}