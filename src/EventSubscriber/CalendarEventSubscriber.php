<?php

namespace App\EventSubscriber;

use App\Repository\ReservationActiviteRepository;
use CalendarBundle\Event\CalendarEvent;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;

class CalendarEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReservationActiviteRepository $reservationRepo,
        private Security $security
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarLoad',
        ];
    }

    public function onCalendarLoad(CalendarEvent $calendarEvent): void
    {
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $reservations = $this->reservationRepo->findBy([
            'user'   => $user,
            'statut' => 'confirmee'
        ]);

        foreach ($reservations as $reservation) {
            $activite = $reservation->getActivite();

            $event = new Event();
            $event->setTitle($activite->getNomActivite());
            $event->setStart($reservation->getDateActivite());
            $event->setBackgroundColor('#8B0000');
            $event->setBorderColor('#3D0000');
            $event->setTextColor('#ffffff');
            
            // Méthode pour définir l'URL de clic
            $event->setOptions([
                'url' => '/client/activite/' . $activite->getId()
            ]);

            $calendarEvent->addEvent($event);
        }
    }
}