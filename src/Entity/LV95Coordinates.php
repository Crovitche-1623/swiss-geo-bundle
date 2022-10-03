<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;

/**
 * @author  Thibault Gattolliat
 */
#[Embeddable]
class LV95Coordinates
{
    #[Column(type: Types::INTEGER, length: 7, nullable: true)]
    private ?int $northing = null;

    #[Column(type: Types::INTEGER, length: 7, nullable: true)]
    private ?int $easting = null;

    public function getNorthing(): ?int
    {
        return $this->northing;
    }

    public function setNorthing(?int $northing): self
    {
        $this->northing = $northing;

        return $this;
    }

    public function getEasting(): ?int
    {
        return $this->easting;
    }

    public function setEasting(?int $easting): self
    {
        $this->easting = $easting;

        return $this;
    }
}
