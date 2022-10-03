<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Crovitche\SwissGeoBundle\Repository\BuildingAddressRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Crovitche\SwissGeoBundle\Enum\StreetOrAddressStatus;
use Crovitche\SwissGeoBundle\Enum\Address\BuildingCategory;

/**
 * @author  Thibault Gattolliat
 */
#[ORM\Entity(BuildingAddressRepository::class, true), ORM\Table("Building_address")]
#[ORM\Index(["id_street_locality"], name: "IX___Building_address___street_locality")]
class BuildingAddress extends AbstractEntity
{
    // We have to override the default strategy defined in AbstractEntity.
    #[ORM\Id, ORM\GeneratedValue("NONE")]
    #[ORM\Column("egaid", Types::INTEGER, 9, options: ["comment" =>
        "N° d'identification fédérale de l'adresse de bâtiments selon le RegBL",
        "unsigned" => true
    ])]
    protected ?int $id = null;

    #[ORM\ManyToOne(StreetLocality::class, fetch: "EAGER")]
    #[ORM\JoinColumn("id_street_locality", "id", false, false, "CASCADE")]
    private ?StreetLocality $streetLocality = null;

    /**
     * Identificateur fédéral de bâtiment
     */
    #[ORM\Column(type: Types::DECIMAL, options: [
        "comment" => "BDG_EGID - Numéro d’identification du bâtiment selon le RegBL"
    ])]
    private ?float $buildingId = null;

    /**
     * Identificateur fédéral des entrées
     * Par entrée du bâtiment on entend l’accès par l’extérieur.
     * L’entrée est identifiée par une adresse de bâtiment.
     */
    #[ORM\Column(type: Types::SMALLINT, options: ["comment" =>
        "Numéro d'identification de l'entrée de bâtiment selon le RegBL"
    ])]
    private ?int $entranceNumber = null;

    /**
     * Numéro (de maison) - peut contenir autre chose que des chiffres
     */
    #[ORM\Column("address_number", length: 12, nullable: true, options: [
        "comment" => "ADR_NUMBER - Numéro (de maison)"
    ])]
    private ?string $number = null;

    #[ORM\Column(length: 50, nullable: true, options: ["comment" =>
        "Nom du bâtiment, uniquement en l’absence de valeur pour address_number"
    ])]
    private ?string $buildingName = null;

    #[ORM\Column(length: 18, enumType: BuildingCategory::class, options: [
        "comment" =>
            "Subdivision des bâtiments selon leur destination, selon le RegBL"
    ])]
    private ?BuildingCategory $buildingCategory = null;

    #[ORM\Column("address_status", length: 8,
        enumType: StreetOrAddressStatus::class,
        options: [
            "comment" => "Etat de réalisation de l’adresse selon le RegBL"
        ]
    )]
    private ?StreetOrAddressStatus $status = null;

    #[ORM\Column(type: Types::BOOLEAN, options: [
        "comment" => "Caractère obligatoire de l’adresse selon le RegBL"
    ])]
    private ?bool $isOfficial = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["comment" =>
        "Fiabilité de l'adresse selon les contrôles (checks de swisstopo)"
    ])]
    private ?bool $isValid = null;

    #[ORM\Embedded(LV95Coordinates::class, columnPrefix: false)]
    private LV95Coordinates $coordinates;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: [
        "comment" => "Date de la dernière modification de l'adresse"
    ])]
    private ?DateTimeImmutable $lastModificationDate = null;

    public function __construct()
    {
        $this->coordinates = new LV95Coordinates();
    }

    public function __toString(): string
    {
        return (string) $this->getNumber();
    }

    public function getStreetLocality(): ?StreetLocality
    {
        return $this->streetLocality;
    }

    public function setStreetLocality(StreetLocality $streetLocality): self
    {
        $this->streetLocality = $streetLocality;

        return $this;
    }

    public function getBuildingId(): ?float
    {
        return $this->buildingId;
    }

    public function setBuildingId(float $buildingId): self
    {
        $this->buildingId = $buildingId;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getEntranceNumber(): ?int
    {
        return $this->entranceNumber;
    }

    public function setEntranceNumber(int $entranceNumber): self
    {
        $this->entranceNumber = $entranceNumber;

        return $this;
    }

    public function getBuildingName(): ?string
    {
        return $this->buildingName;
    }

    public function setBuildingName(?string $buildingName): self
    {
        $this->buildingName = $buildingName;

        return $this;
    }

    public function getBuildingCategory(): BuildingCategory
    {
        return $this->buildingCategory;
    }

    public function setBuildingCategory(BuildingCategory $buildingCategory): self
    {
        $this->buildingCategory = $buildingCategory;

        return $this;
    }

    public function getIsOfficial(): ?bool
    {
        return $this->isOfficial;
    }

    public function setIsOfficial(bool $isOfficial): self
    {
        $this->isOfficial = $isOfficial;

        return $this;
    }

    public function getStatus(): ?StreetOrAddressStatus
    {
        return $this->status;
    }

    public function setStatus(StreetOrAddressStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCoordinates(): LV95Coordinates
    {
        return $this->coordinates;
    }

    public function setCoordinates(LV95Coordinates $coordinates): self
    {
        $this->coordinates = $coordinates;

        return $this;
    }

    public function getLastModificationDate(): ?DateTimeImmutable
    {
        return $this->lastModificationDate;
    }

    public function setLastModificationDate(DateTimeImmutable $lastModificationDate): self
    {
        $this->lastModificationDate = $lastModificationDate;

        return $this;
    }
}
