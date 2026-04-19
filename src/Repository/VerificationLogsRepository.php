<?php

namespace App\Repository;

use App\Entity\VerificationLogs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VerificationLogsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationLogs::class);
    }

    public function findValidCode(int $userId, string $code, string $type): ?VerificationLogs
    {
        return $this->createQueryBuilder('v')
            ->join('v.user', 'u')
            ->where('u.idUser = :userId')
            ->andWhere('v.code = :code')
            ->andWhere('v.codeType = :type')
            ->andWhere('v.expiresAt > :now')
            ->andWhere('v.used = false')
            ->setParameter('userId', $userId)
            ->setParameter('code', $code)
            ->setParameter('type', $type)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }
}