<?php

namespace App\Repository;

use App\Entity\Activite;
use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /** @return Avis[] */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->leftJoin('a.activite', 'ac')->addSelect('ac')
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Avis[] */
    public function findByActiviteOrdered(Activite $activite): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->where('a.activite = :act')
            ->setParameter('act', $activite)
            ->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche (commentaire, voyageur, activité) + filtre note + filtre activité.
     * $noteFilter : null|'' = toutes, 'none' = sans note, '1'..'5' = note exacte.
     *
     * @return Avis[]
     */
    public function searchAndFilter(?string $search, ?string $noteFilter, ?int $activiteId): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->leftJoin('a.activite', 'ac')->addSelect('ac');

        if (null !== $search && '' !== $search) {
            $qb->andWhere(
                'a.commentaire LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q OR ac.nomActivite LIKE :q'
            )->setParameter('q', '%'.$search.'%');
        }

        if (null !== $noteFilter && '' !== $noteFilter) {
            if ('none' === $noteFilter) {
                $qb->andWhere('a.note IS NULL');
            } elseif (ctype_digit($noteFilter)) {
                $qb->andWhere('a.note = :note')->setParameter('note', (int) $noteFilter);
            }
        }

        if (null !== $activiteId && $activiteId > 0) {
            $qb->andWhere('a.activite = :aid')
                ->setParameter('aid', $activiteId);
        }

        return $qb->orderBy('a.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
