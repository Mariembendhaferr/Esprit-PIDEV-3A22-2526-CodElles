<?php

namespace App\EventSubscriber;

use App\Repository\ReservationActiviteRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReservationActiviteRepository $reservationRepo,
        private Security $security,
        private UrlGeneratorInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarLoad',
        ];
    }

    /*public function onCalendarLoad(CalendarEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user) return;

        $start = $event->getStart();
        $end   = $event->getEnd();

        $reservations = $this->reservationRepo->findByUserAndPeriod($user, $start, $end);

            foreach ($reservations as $reservation) {
                $activite = $reservation->getActivite();
                if (!$activite) continue;

                // Create the event
                $calEvent = new Event(
                    $activite->getNomActivite() . ' (' . $reservation->getNombreParticipants() . ' pers.)',
                    $reservation->getDateActivite() // Must be a DateTime object
                );

                $calEvent->setOptions([
                    'backgroundColor' => '#8B0000',
                    'borderColor'     => '#C9A84C',
                    'textColor'       => '#ffffff',
                    'url'             => $this->router->generate('app_client_activity_show', [
                        'id' => $activite->getId()
                    ])
                ]);

                $event->addEvent($calEvent);
            }
    }*/




public function onCalendarLoad(\CalendarBundle\Event\CalendarEvent $event): void
{
    $start = $event->getStart();
    $end   = $event->getEnd();

    // Query for user ID 18
    $reservations = $this->reservationRepo->findByStaticUserAndPeriod(18, $start, $end);

    foreach ($reservations as $res) {
        $date = $res->getDateActivite();
        if (!$date) continue; // Skip if no date

        // Get activity name or fallback
        $title = "Réservation";
        if ($res->getActivite()) {
            $title = $res->getActivite()->getNomActivite();
        }

        $calEvent = new \CalendarBundle\Entity\Event(
            $title . ' (' . $res->getNombreParticipants() . ' pers.)',
            $date
        );

        $calEvent->setOptions([
            'backgroundColor' => '#8B0000',
            'borderColor'     => '#C9A84C',
            'textColor'       => '#ffffff',
            // If you have a show route, uncomment this:
            // 'url' => $this->router->generate('app_client_activity_show', ['id' => $res->getActivite()->getId()])
        ]);

        $event->addEvent($calEvent);
    }
}
}