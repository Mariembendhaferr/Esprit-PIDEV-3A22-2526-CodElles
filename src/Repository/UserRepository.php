<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countTotalUsers(): int
    {
        return $this->count([]);
    }

    public function countActiveUsers(): int
    {
        return $this->count(['statut' => 'actif']);
    }

    public function countInactiveUsers(): int
    {
        return $this->count(['statut' => 'inactif']);
    }

    public function countByRole(string $role): int
    {
        return $this->count(['role' => $role]);
    }

    public function getMonthlyNewUsers(): array
    {
        $start = new \DateTime('first day of this month 00:00:00');
        $end   = new \DateTime('last day of this month 23:59:59');

        return $this->createQueryBuilder('u')
            ->where('u.dateInscription BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function getWeeklyNewUsers(): array
    {
        $start = new \DateTime('monday this week 00:00:00');
        $end   = new \DateTime('sunday this week 23:59:59');

        return $this->createQueryBuilder('u')
            ->where('u.dateInscription BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function getRegistrationsLast7Days(): array
    {
        $results = $this->createQueryBuilder('u')
            ->select('DATE(u.dateInscription) as date, COUNT(u.idUser) as count')
            ->where('u.dateInscription >= :week')
            ->setParameter('week', new \DateTime('-7 days'))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($results as $row) {
            $data[$row['date']] = (int) $row['count'];
        }

        return $data;
    }
    public function searchUsers(
    string $search = '',
    string $searchBy = 'nom',
    string $statut = 'tous',
    string $role = 'tous'
): array {
    $qb = $this->createQueryBuilder('u');

    if (!empty($search)) {
        $search = strtolower(trim($search));
        match($searchBy) {
            'username' => $qb->andWhere('LOWER(u.username) LIKE :search'),
            'email'    => $qb->andWhere('LOWER(u.email) LIKE :search'),
            default    => $qb->andWhere(
                'LOWER(u.nom) LIKE :search OR LOWER(u.prenom) LIKE :search'
            ),
        };
        $qb->setParameter('search', '%' . $search . '%');
    }

    if ($statut !== 'tous') {
        $qb->andWhere('u.statut = :statut')
           ->setParameter('statut', $statut);
    }

    if ($role !== 'tous') {
        $qb->andWhere('u.role = :role')
           ->setParameter('role', $role);
    }

    return $qb->orderBy('u.dateInscription', 'DESC')
              ->getQuery()
              ->getResult();
}
public function findByUsernameOrEmail(string $input): ?User
{
    return $this->createQueryBuilder('u')
        ->where('u.username = :input OR u.email = :input')
        ->setParameter('input', $input)
        ->getQuery()
        ->getOneOrNullResult();
}
}