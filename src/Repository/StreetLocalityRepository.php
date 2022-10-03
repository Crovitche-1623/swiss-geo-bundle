<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Repository;

use Crovitche\SwissGeoBundle\Entity\StreetLocality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author  Thibault Gattolliat
 *
 * @extends ServiceEntityRepository<StreetLocality>
 *
 * @method  StreetLocality|null  find($id, $lockMode = null, $lockVersion = null)
 * @method  StreetLocality|null  findOneBy(array $criteria, array $orderBy = null)
 * @method  StreetLocality[]     findAll()
 * @method  StreetLocality[]     findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StreetLocalityRepository extends ServiceEntityRepository
{
    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StreetLocality::class);
    }
}
