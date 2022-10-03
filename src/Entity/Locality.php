<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Crovitche\SwissGeoBundle\Repository\LocalityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author  Thibault Gattolliat
 */
#[ORM\Entity(LocalityRepository::class, true), ORM\Table("Locality")]
#[ORM\UniqueConstraint("UQ___Locality___label__postal_code",
    ["postal_code_and_label"]
)]
#[ORM\Index(["postal_code_and_label"],
    name: "IX___Locality___postal_code_and_label"
)]
class Locality extends AbstractEntity
{
    #[ORM\Column(length: 100)]
    public ?string $label = null;

    #[ORM\Column(length: 4, options: ["fixed" => true])]
    public ?string $postalCode = null;

    #[ORM\Column(length: 2, options: ["fixed" => true])]
    public ?string $regionAbbreviation = null;

    #[ORM\Column(type: Types::SMALLINT, length: 2, options: [
        "unsigned" => true
    ])]
    public ?int $additionalDigits = null;

    /**
     * Generated column based on three field. This column exists for performance
     * reasons.
     *
     * Note: Length is 110 because label is 100, postal code is 4 and additional
     *       digit is 6.
     */
    #[ORM\Column(length: 110, insertable: false, updatable: false,
        columnDefinition: "VARCHAR(110) GENERATED ALWAYS AS (CONCAT(CONCAT(postal_code, ' '), label)) STORED",
        generated: "ALWAYS"
    )]
    public ?string $postalCodeAndLabel = null;

    public function __toString(): string
    {
        return (string) $this->postalCodeAndLabel;
    }

    /**
     * {@inheritDoc}
     */
    public function getUniqueValue(): string
    {
        return
            $this->postalCodeAndLabel .
            '_' .
            $this->regionAbbreviation
            ;
    }

    public function getSixDigitsPostalCode(): ?string
    {
        return $this->postalCode . $this->additionalDigits;
    }
}
