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
    /**
     * @var  null|int<0,  16777215>
     */
    #[Column(
        name: 'lv95_northing',
        type: Types::INTEGER,
        length: 7,
        options: ['unsigned' => true],
        columnDefinition: 'MEDIUMINT UNSIGNED'
    )]
    private ?int $northing = null;

    /**
     * @var  null|int<0,  16777215>
     */
    #[Column(
        name: 'lv95_easting',
        type: Types::INTEGER,
        length: 7,
        options: ['unsigned' => true],
        columnDefinition: 'MEDIUMINT UNSIGNED'
    )]
    private ?int $easting = null;

    /**
     * @return  null|int<0,  16777215>
     */
    public function getNorthing(): ?int
    {
        return $this->northing;
    }

    /**
     * @param  null|int<0,  16777215>  $northing
     *
     * @return  static
     */
    public function setNorthing(?int $northing): self
    {
        $this->northing = $northing;

        return $this;
    }

    /**
     * @return  null|int<0,  16777215>
     */
    public function getEasting(): ?int
    {
        return $this->easting;
    }

    /**
     * @param  null|int<0,  16777215>  $easting
     *
     * @return  static
     */
    public function setEasting(?int $easting): self
    {
        $this->easting = $easting;

        return $this;
    }
}
