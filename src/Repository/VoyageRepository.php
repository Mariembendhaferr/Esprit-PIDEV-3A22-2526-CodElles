<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    /**
     * Récupère tous les voyages SAUF ceux avec "voyage personnalisé"
     */
    public function findAllWithoutCustom(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.titre NOT LIKE :keyword')
            ->setParameter('keyword', '%voyage personnalisé%')
            ->orderBy('v.id_voyage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les voyages d'un pays SAUF ceux avec "voyage personnalisé"
     */
    public function findByDestinationWithoutCustom(string $destination): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.destination = :destination')
            ->andWhere('v.titre NOT LIKE :keyword')
            ->setParameter('destination', $destination)
            ->setParameter('keyword', '%voyage personnalisé%')
            ->orderBy('v.budget_estime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de pays distincts (sans voyages personnalisés)
     */
    public function countDistinctPays(): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v.destination)')
            ->where('v.titre NOT LIKE :keyword')
            ->setParameter('keyword', '%voyage personnalisé%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les pays distincts avec leur continent
     */
    public function findAllPays(): array
    {
        return $this->createQueryBuilder('v')
            ->select('DISTINCT v.destination, v.continent')
            ->where('v.titre NOT LIKE :keyword')
            ->setParameter('keyword', '%voyage personnalisé%')
            ->orderBy('v.destination', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les voyages pour l'admin (sans filtre "personnalisé")
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.id_voyage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    

    /**
     * Récupère un voyage par son ID avec ses relations
     */
    public function findOneWithRelations(int $id): ?Voyage
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.planjournaliers', 'p')
            ->addSelect('p')
            ->where('v.id_voyage = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}