<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;

/**
 * @author  Thibault Gattolliat
 *
 * @see  https://www.swisstopo.admin.ch/content/swisstopo-internet/fr/topics/survey/reference-systems/switzerland/_jcr_content/contentPar/tabs/items/dokumente_publikatio/tabPar/downloadlist/downloadItems/516_1459343097192.download/ch1903wgs84_f.pdf
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
     * @see /doc/ch1903wgs84_e-2.pdf  for more details on formulas.
     *
     * @return  float|null  null if easting or northing are missing
     */
    public function getWGSLongitude(): ?float
    {
        if (!$this->getY() || !$this->getX()) {
            return null;
        }

        // Converts military to civil and to unit = 1000km
        // Auxiliary values (% Bern)
        $yAux = ($this->getY() - 600000) / 1000000;
        $xAux = ($this->getX() - 200000) / 1000000;

        $longitude = 2.6779094
             + 4.728982 * $yAux
             + 0.791484 * $yAux * $xAux
             + 0.1306   * $yAux * ($xAux ** 2)
             - 0.0436   * ($yAux ** 3);

        // Unit 10000" to 1 " and converts seconds to degrees (dec)
        return $longitude * 100 / 36;
    }

    /**
     * @see  /doc/ch1903wgs84_e-2.pdf  for more details on formulas.
     *
     * @return  float|null  null if easting or northing are missing
     */
    public function getWGSLatitude(): ?float
    {
        if (!$this->getY() || !$this->getX()) {
            return null;
        }

        // Converts military to civil and to unit = 1000km
        // Auxiliary values (% Bern)
        $yAux = ($this->getY() - 600000) / 1000000;
        $xAux = ($this->getX() - 200000) / 1000000;

        $latitude = 16.9023892
            +  3.238272 * $xAux
            -  0.270978 * ($yAux ** 2)
            -  0.002528 * ($xAux ** 2)
            -  0.0447   * ($yAux ** 2) * $xAux
            -  0.0140   * ($xAux ** 3);

        // Unit 10000" to 1 " and converts seconds to degrees (dec)
        return $latitude * 100 / 36;
    }

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
    public function getX(): ?int
    {
        return $this->northing;
    }

    /**
     * @param  null|int<0, 16777215>  $x
     *
     * @return  static
     */
    public function setX(?int $x): self
    {
        $this->northing = $x;

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

    /**
     * @return  null|int<0,  16777215>
     */
    public function getY(): ?int
    {
        return $this->easting;
    }

    /**
     * @param  null|int<0, 16777215>  $y
     *
     * @return  static
     */
    public function setY(?int $y): self
    {
        $this->easting = $y;

        return $this;
    }
}
