<?php

namespace App\Repository;

use App\Entity\Activite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    public function searchAndFilter(?string $search, ?string $category, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($search) {
            $qb->andWhere('a.nomActivite LIKE :s OR a.localisationActivite LIKE :s OR a.categorieActivite LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        if ($category && $category !== 'all') {
            $qb->andWhere('a.categorieActivite = :cat')
               ->setParameter('cat', $category);
        }

        match ($sort) {
            'prix_asc'   => $qb->orderBy('a.coutActivite', 'ASC'),
            'prix_desc'  => $qb->orderBy('a.coutActivite', 'DESC'),
            'duree_asc'  => $qb->orderBy('a.dureeActivite', 'ASC'),
            'duree_desc' => $qb->orderBy('a.dureeActivite', 'DESC'),
            default      => $qb->orderBy('a.id', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function countByCategory(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.categorieActivite as categorie, COUNT(a) as total')
            ->groupBy('a.categorieActivite')
            ->getQuery()->getResult();
    }

    public function findRelatedActivities(Activite $activite, int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.categorieActivite = :category')
            ->andWhere('a.id != :id')
            // ->andWhere('a.disponibiliteActivite = true')  // REMOVED (as requested)
            ->setParameter('category', $activite->getCategorieActivite())
            ->setParameter('id', $activite->getId())
            ->orderBy('a.id', 'DESC')  // stable + fast
            ->setMaxResults($limit);

        // If your related cards use a.fournisseurs, add this to avoid lazy-loading:
        $qb->leftJoin('a.fournisseurs', 'f')
        ->addSelect('f');

        return $qb->getQuery()->getResult();
    }

        /**
     * Find all activities booked by a specific user
     */
    public function findActivitiesBookedByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.bookedByUsers', 'u')
            ->where('u.idUser = :userId')
            ->setParameter('userId', $user->getIdUser())
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }
 
    /**
     * Find activities with available places
     */
    public function findAvailableActivities(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.placesReserves < a.capaciteMaxActivite')
            ->andWhere('a.disponibiliteActivite = true')
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }
 
    /**
     * Find activities that are full
     */
    public function findFullActivities(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.placesReserves >= a.capaciteMaxActivite')
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }
 
    /**
     * Get booking statistics
     */
    public function getBookingStats(): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('COUNT(a.idActivite) as totalActivities')
            ->addSelect('SUM(a.capaciteMaxActivite) as totalCapacity')
            ->addSelect('SUM(a.placesReserves) as totalReserved')
            ->getQuery()
            ->getOneOrNullResult();
 
        return $result ?? [
            'totalActivities' => 0,
            'totalCapacity' => 0,
            'totalReserved' => 0,
        ];
    }
}