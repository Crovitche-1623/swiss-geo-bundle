<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Repository;

use Crovitche\SwissGeoBundle\Entity\BuildingAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\{AbstractQuery, NonUniqueResultException, Query, QueryBuilder};
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author  Thibault Gattolliat
 *
 * @extends ServiceEntityRepository<BuildingAddress>
 *
 * @method  BuildingAddress|null  find($id, $lockMode = null, $lockVersion = null)
 * @method  BuildingAddress|null  findOneBy(array $criteria, array $orderBy = null)
 * @method  BuildingAddress[]     findAll()
 * @method  BuildingAddress[]     findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BuildingAddressRepository extends ServiceEntityRepository
{
    public const API_ADDRESSES_PER_PAGE = 7;

    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BuildingAddress::class);
    }

    public function findAllByStreetLocality(
        int $streetLocalityId,
        ?string $number = null
    ): Collection {
        $qb = $this->getFindAllByStreetLocalityQueryBuilder($streetLocalityId, $number, isSearchQuery: true);
        $query = $qb->getQuery();
        $query->setHint(Query::HINT_READ_ONLY, true);

        return new ArrayCollection($query->getResult());
    }

    public function getFindAllByStreetLocalityQueryBuilder(
        int $streetLocalityId,
        ?string $addressNumber = null,
        bool $isSearchQuery = false
    ): QueryBuilder {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('a0')
            ->from(BuildingAddress::class, 'a0')
            ->innerJoin('a0.streetLocality', 's1')
            ->innerJoin('s1.street', 's2')
            // TODO: Rechercher si il y a d'autre type qui n'ont pas d'address number par exemple
            ->andWhere("s2.type = 'street'")
            ->andWhere("s2.status <> 'outdated'")
            ->andWhere('s1.id = :street_locality_id')
            ->setParameter('street_locality_id', $streetLocalityId)
        ;

        if ($isSearchQuery) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull(':address_number'),
                        $qb->expr()->like('a0.number', ':address_number')
                    )
                )
                ->setParameter('address_number', '%'.$addressNumber.'%')
                ->addOrderBy('LENGTH(a0.number)')
                ->addOrderBy('a0.number')
                ->setMaxResults(self::API_ADDRESSES_PER_PAGE)
            ;
        }

        return $qb;
    }

    /**
     * @throws  NonUniqueResultException
     */
    public function findOneByRegionAndLocalityAndStreetAndNumber(
        string $number,
        string $regionAbbreviation,
        string|int $localityId,
        string|int $streetId,
    ): ?BuildingAddress {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('a0')
            ->from(BuildingAddress::class, 'a0')
            ->innerJoin('a0.streetLocality', 's1')
            ->innerJoin('s1.locality', 'l2')
            ->innerJoin('s1.street', 's3')
            ->andWhere($qb->expr()->eq('a0.id', ':egaid'))
            ->andWhere($qb->expr()->eq('l2.regionAbbreviation', ':regionAbbreviation'))
            ->andWhere($qb->expr()->eq('l2.id', ':localityId'))
            ->andWhere($qb->expr()->eq('s3.id', ':streetId'))
            ->andWhere($qb->expr()->eq('s3.type', "'street'"))
            ->andWhere($qb->expr()->neq('s3.status', "'outdated'"))
        ;
        $qb->setParameter('egaid', $number, ParameterType::STRING);
        $qb->setParameter('regionAbbreviation', $regionAbbreviation, ParameterType::STRING);
        $qb->setParameter('localityId', (int) $localityId, ParameterType::INTEGER);
        $qb->setParameter('streetId', (int) $streetId, ParameterType::INTEGER);
        $query = $qb->getQuery();
        $query->setHint(Query::HINT_READ_ONLY, true);
        $query->setHydrationMode(AbstractQuery::HYDRATE_OBJECT);

        return $query->getOneOrNullResult();
    }
}
