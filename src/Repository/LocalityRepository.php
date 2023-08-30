<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Repository;

use Crovitche\SwissGeoBundle\Entity\Locality;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author  Thibault Gattolliat
 *
 * @extends ServiceEntityRepository<Locality>
 *
 * @method  Locality|null  find($id, $lockMode = null, $lockVersion = null)
 * @method  Locality|null  findOneBy(array $criteria, array $orderBy = null)
 * @method  Locality[]     findAll()
 * @method  Locality[]     findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocalityRepository extends ServiceEntityRepository
{
    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Locality::class);
    }

    /**
     * @throws  \Exception
     */
    public function findAllBySearchCriteria(
        ?string $regionAbbreviation = null,
        ?string $postalCodeAndLabel = null
    ): Collection {
        $connection = $this->getEntityManager()->getConnection();

        // SQL Query must be used instead of DQL because we have a generated
        // column. FIXME:
        $resultStatement = $connection->executeQuery(
            sql: '
                SELECT
                   l0.id,
                   l0.postal_code,
                   l0.label,
                   l0.postal_code_and_label,
                   l0.additional_digits,
                   l0.region_abbreviation
                FROM
                   Locality l0
                WHERE
                   (
                       l0.region_abbreviation IS NULL OR
                       l0.region_abbreviation = :region_abbreviation
                   )
                   AND
                   (
                       :postal_code_and_label IS NULL OR
                        l0.postal_code_and_label LIKE :postal_code_and_label
                   )
                ORDER BY
                   l0.postal_code_and_label
                LIMIT 7;
            ',
            params: [
                'region_abbreviation' => $regionAbbreviation,
                'postal_code_and_label' => '%'.$postalCodeAndLabel.'%',
            ]
        );

        $localitiesAsArray = $resultStatement->fetchAllAssociative();

        $localities = new ArrayCollection();

        foreach ($localitiesAsArray as $localityData) {
            $locality = new Locality();
            $locality->setId((int) $localityData['id']);
            $locality->label = $localityData['label'];
            $locality->regionAbbreviation = $localityData['region_abbreviation'];
            $locality->postalCode = $localityData['postal_code'];
            $locality->additionalDigits = (int) $localityData['additional_digits'];
            $locality->postalCodeAndLabel = $localityData['postal_code_and_label'];
            $localities->add($locality);
        }

        return $localities;
    }
}
