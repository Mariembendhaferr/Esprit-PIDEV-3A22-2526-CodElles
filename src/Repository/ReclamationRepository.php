<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    /** @return Reclamation[] */
    public function findAllWithUser(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->orderBy('r.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche texte (titre, description, nom/prénom demandeur) + filtres statut / priorité.
     *
     * @return Reclamation[]
     */
    public function searchAndFilter(?string $search, ?string $statut, ?string $priorite): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u');

        if (null !== $search && '' !== $search) {
            $qb->andWhere(
                'r.titre LIKE :q OR r.description LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q'
            )->setParameter('q', '%'.$search.'%');
        }

        if (null !== $statut && '' !== $statut) {
            $qb->andWhere('r.statut = :st')->setParameter('st', $statut);
        }

        if (null !== $priorite && '' !== $priorite) {
            $qb->andWhere('r.priorite = :pr')->setParameter('pr', $priorite);
        }

        return $qb->orderBy('r.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
