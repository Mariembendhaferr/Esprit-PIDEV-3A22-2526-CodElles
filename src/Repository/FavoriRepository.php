<?php

namespace App\Repository;

use App\Entity\Favori;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ✅ Fix: générique spécifié
 * @extends ServiceEntityRepository<Favori>
 */
class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }

    /** @return array<int, Favori> */
    public function findFavorisByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.voyage', 'v')
            ->addSelect('v')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.date_ajout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isFavori(User $user, int $voyageId): bool
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id_favori)')
            ->where('f.user = :user')
            ->andWhere('f.voyage = :voyageId')
            ->setParameter('user', $user)
            ->setParameter('voyageId', $voyageId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findFavoriByUserAndVoyage(User $user, int $voyageId): ?Favori
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.voyage = :voyageId')
            ->setParameter('user', $user)
            ->setParameter('voyageId', $voyageId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}