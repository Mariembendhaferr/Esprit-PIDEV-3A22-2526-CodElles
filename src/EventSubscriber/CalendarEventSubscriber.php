<?php

namespace App\EventSubscriber;

use App\Repository\ReservationActiviteRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CalendarEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReservationActiviteRepository $reservationRepo,
        private RequestStack $requestStack
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarLoad',
        ];
    }

    public function onCalendarLoad(CalendarEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        // On récupère l'ID en session, ou 18 par défaut pour le test
        $userId = $request ? $request->getSession()->get('user_id') : 18;
        if (!$userId) {
            $userId = 18; 
        }

        $start = $event->getStart();
        $end   = $event->getEnd();

        // REQUÊTE CORRIGÉE (Flèches -> partout)
        $reservations = $this->reservationRepo->createQueryBuilder('r')
            ->where('r.user = :userId')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateActivite BETWEEN :start AND :end')
            ->setParameter('userId', $userId)
            ->setParameter('statut', 'confirmee')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        // Vérification de sécurité pour Intelephense
        if (!is_iterable($reservations)) {
            return;
        }

        foreach ($reservations as $res) {
            $act = $res->getActivite();
            
            $calEvent = new Event(
                $act ? $act->getNomActivite() : "Réservation #" . $res->getId(),
                $res->getDateActivite()
            );

            // Options graphiques
            $calEvent->setOptions([
                'backgroundColor' => '#8B0000',
                'borderColor'     => '#3D0000',
                'textColor'       => '#ffffff',
            ]);

            // Propriétés pour la Modal (extendedProps)
            $calEvent->addOption('reservationId', $res->getId());
            $calEvent->addOption('activityId', $act ? $act->getId() : null);
            $calEvent->addOption('participants', $res->getNombreParticipants());
            $calEvent->addOption('location', $act ? $act->getLocalisationActivite() : 'Non spécifié');
            $calEvent->addOption('price', $act ? $act->getCoutActivite() : 0);

            $event->addEvent($calEvent);
        }
    }
}