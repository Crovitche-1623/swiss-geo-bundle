<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Crovitche\SwissGeoBundle\Repository\StreetRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Crovitche\SwissGeoBundle\Enum\Street\Type;
use Crovitche\SwissGeoBundle\Enum\StreetOrAddressStatus;

/**
 * @author  Thibault Gattolliat
 *
 *
 * Area and place are also considered as street in Switzerland topography
 * system.
 */
#[ORM\Entity(StreetRepository::class, true), ORM\Table("Street")]
#[ORM\Index(["label"], name: "FTIX___Street___label", flags: ["fulltext"])]
class Street extends AbstractEntity
{
    // We have to override the default strategy defined in AbstractEntity
    #[ORM\Id, ORM\GeneratedValue('NONE')]
    #[ORM\Column("esid", Types::INTEGER, options: [
        "comment" => "Identificateur fédéral de rue",
        "unsigned" => true
    ])]
    protected ?int $id = null;

    #[ORM\Column("label", length: 150)]
    private ?string $name = null;

    /**
     * @var  Collection<int, StreetLocality>
     */
    #[ORM\OneToMany("street", StreetLocality::class, orphanRemoval: true)]
    private Collection $streetLocality;

    #[ORM\Column(length: 6, enumType: Type::class, options: [
        "comment" => "Genre d’objet (rue, place ou autre)"
    ])]
    private ?Type $type = null;

    #[ORM\Column(length: 8, enumType: StreetOrAddressStatus::class, options: [
        "comment" => "Etat de réalisation de la rue selon le RegBL"
    ])]
    private ?StreetOrAddressStatus $status = null;

    /**
     * Désignation officielle de la rue
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ["comment" =>
        "Caractère obligatoire de l’orthographe du nom de la rue selon le RegBL"
    ])]
    private ?bool $isOfficial = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["comment" =>
        "Fiabilité de l‘adresse selon les contrôles (checks) de swisstopo"
    ])]
    private ?bool $isValid = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ["comment" =>
        "Date de la dernière modification de la rue"
    ])]
    private ?DateTimeImmutable $lastModificationDate = null;

    #[Pure]
    public function __construct()
    {
        $this->streetLocality = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }


    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(Type $type): self
    {
        $this->type = $type;

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

    public function getIsOfficial(): ?bool
    {
        return $this->isOfficial;
    }

    public function setIsOfficial(bool $isOfficial): self
    {
        $this->isOfficial = $isOfficial;

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

    /**
     * @return  Collection<int, StreetLocality>
     */
    public function getStreetLocality(): Collection
    {
        return $this->streetLocality;
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
