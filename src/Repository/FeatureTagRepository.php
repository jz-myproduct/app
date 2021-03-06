<?php

namespace App\Repository;

use App\Entity\FeatureTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeatureTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeatureTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeatureTag[]    findAll()
 * @method FeatureTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeatureTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureTag::class);
    }
}
