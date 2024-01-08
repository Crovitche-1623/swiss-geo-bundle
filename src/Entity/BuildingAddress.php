<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Crovitche\SwissGeoBundle\Enum\Address\BuildingCategory;
use Crovitche\SwissGeoBundle\Enum\StreetOrAddressStatus;
use Crovitche\SwissGeoBundle\Repository\BuildingAddressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author  Thibault Gattolliat
 */
#[ORM\Entity(BuildingAddressRepository::class, true), ORM\Table('Building_address')]
#[ORM\Index(['id_street_locality'], name: 'IX___Building_address___street_locality')]
class BuildingAddress
{
    /**
     * N° d'identification fédérale de l'adresse de bâtiments selon le RegBL
     */
    #[ORM\Id, ORM\GeneratedValue('NONE')]
    #[ORM\Column('egaid', Types::INTEGER, 9, options: [
        'unsigned' => true,
    ])]
    private ?int $id = null;

    #[ORM\ManyToOne(StreetLocality::class, fetch: 'EAGER')]
    #[ORM\JoinColumn('id_street_locality', 'id', false, false, 'CASCADE')]
    private ?StreetLocality $streetLocality = null;

    /**
     * Identificateur fédéral de bâtiment
     *
     * Numéro d’identification du bâtiment selon le RegBL
     *
     * Note :
     * Doctrine retourne une chaîne de caractères pour les chiffres à virgules.
     */
    #[ORM\Column(type: Types::DECIMAL)]
    private ?string $buildingId = null;

    /**
     * Identificateur fédéral des entrées
     * Par entrée du bâtiment, on entend l’accès par l’extérieur.
     * L’entrée est identifiée par une adresse de bâtiment.
     *
     * Numéro d'identification de l'entrée de bâtiment selon le RegBL
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $entranceNumber = null;

    /**
     * Numéro (de maison) - peut contenir autre chose que des chiffres
     *
     * ADR_NUMBER - Numéro (de maison)
     */
    #[ORM\Column('address_number', length: 12, nullable: true)]
    private ?string $number = null;

    /**
     * Nom du bâtiment, uniquement en l’absence de valeur pour address_number
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $buildingName = null;

    /**
     * Subdivision des bâtiments selon leur destination, selon le RegBL
     */
    #[ORM\Column(length: 18, enumType: BuildingCategory::class)]
    private ?BuildingCategory $buildingCategory = null;

    /**
     * État de réalisation de l’adresse selon le RegBL
     */
    #[ORM\Column('completion_status', length: 8,
        enumType: StreetOrAddressStatus::class
    )]
    private ?StreetOrAddressStatus $status = null;

    /**
     * Caractère obligatoire de l’adresse selon le RegBL
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isOfficial = null;

    #[ORM\Embedded(LV95Coordinates::class, columnPrefix: false)]
    private LV95Coordinates $coordinates;

    /**
     * Date de la dernière modification de l'adresse
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $lastModificationDate = null;

    public function __construct()
    {
        $this->coordinates = new LV95Coordinates();
    }

    public function __toString(): string
    {
        return (string) $this->getNumber();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return  static
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return  string  An unique value for cache purpose or data fixtures.
     *                  avoid the id if possible because the id require the
     *                  entity to be persisted. It can be a combinaison of
     *                  multiple fields.
     */
    public function getUniqueValue(): string
    {
        return (string) $this->getId();
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

    public function getBuildingId(): ?string
    {
        return $this->buildingId;
    }

    public function setBuildingId(?string $buildingId): self
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

    public function getBuildingCategory(): ?BuildingCategory
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

    public function getLastModificationDate(): ?\DateTimeImmutable
    {
        return $this->lastModificationDate;
    }

    public function setLastModificationDate(\DateTimeImmutable $lastModificationDate): self
    {
        $this->lastModificationDate = $lastModificationDate;

        return $this;
    }
}
