<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Crovitche\SwissGeoBundle\Repository\StreetLocalityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author  Thibault Gattolliat
 *
 * A street (or place/area) can be on many localities this entity is a join
 * table.
 */
#[ORM\Entity(StreetLocalityRepository::class, true)]
#[ORM\Index(columns: ['id_street'], name: 'IX___Street__Locality___street')]
#[ORM\Index(columns: ['id_locality'], name: 'IX___Street__Locality___locality')]
#[ORM\Table('Street__Locality')]
#[ORM\UniqueConstraint('UQ___Street__Locality___street__locality', [
    'id_street', 'id_locality',
])]
class StreetLocality extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: Street::class, inversedBy: 'streetLocality')]
    #[ORM\JoinColumn('id_street', 'esid', false, false, 'CASCADE')]
    private ?Street $street = null;

    #[ORM\ManyToOne(targetEntity: Locality::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'id_locality', nullable: false)]
    private ?Locality $locality = null;

    public function getStreet(): ?Street
    {
        return $this->street;
    }

    public function setStreet(Street $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getLocality(): ?Locality
    {
        return $this->locality;
    }

    public function setLocality(Locality $locality): self
    {
        $this->locality = $locality;

        return $this;
    }
}
