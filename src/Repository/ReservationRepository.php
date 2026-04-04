<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Find reservations with filters
     */
    public function findByFilters(?string $search, ?string $statut, ?\DateTimeInterface $dateDebut, ?\DateTimeInterface $dateFin): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.client', 'c')
            ->addSelect('c');

        if ($search) {
            $qb->andWhere('c.nom LIKE :search OR c.prenom LIKE :search OR r.id LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }
        if ($statut && $statut !== 'Tous') {
            $qb->andWhere('r.statut = :statut')
               ->setParameter('statut', $statut);
        }
        if ($dateDebut) {
            $qb->andWhere('r.dateDepart >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('r.dateDepart <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->orderBy('r.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}