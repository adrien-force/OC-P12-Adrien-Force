<?php

namespace App\Repository;

use App\Entity\GardeningTip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GardeningTip>
 */
class GardeningTipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GardeningTip::class);
    }

    public function findByMonth(int $month): array
    {
        return $this->createQueryBuilder('g')
            ->where('MONTH(g.creationDate) = :month')
            ->setParameter('month', $month)
            ->getQuery()
            ->getResult();

    }
}
