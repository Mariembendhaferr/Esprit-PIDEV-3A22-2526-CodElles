<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    /**
     * Find payments for a specific reservation
     */
    public function findByReservation(int $reservationId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.reservation = :reservationId')
            ->setParameter('reservationId', $reservationId)
            ->getQuery()
            ->getResult();
    }
}