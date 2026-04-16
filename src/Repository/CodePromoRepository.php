<?php

namespace App\Repository;

use App\Entity\CodePromo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CodePromoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromo::class);
    }

    public function findActiveCodes(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isUsed = false')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findValidCode(string $code): ?CodePromo
    {
        return $this->createQueryBuilder('c')
            ->where('c.code = :code')
            ->andWhere('c.isUsed = false')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('code', $code)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function useCode(CodePromo $code): void
    {
        $code->incrementUses();
        $this->getEntityManager()->flush();
    }
}