<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Crovitche\SwissGeoBundle\Enum\Street\Type;
use Crovitche\SwissGeoBundle\Enum\StreetOrAddressStatus;
use Crovitche\SwissGeoBundle\Repository\StreetRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 *
 * Area and place are also considered as street in Switzerland topography
 * system.
 */
#[ORM\Entity(StreetRepository::class, true), ORM\Table('Street')]
#[ORM\Index(
    columns: ['label'],
    name: 'FTIX___Street___label',
    flags: ['fulltext'],
)]
class Street
{
    /**
     * Identificateur fédéral de rue
     */
    #[ORM\Id, ORM\GeneratedValue('NONE')]
    #[ORM\Column('esid', Types::INTEGER, options: [
        'unsigned' => true,
    ])]
    private ?int $id = null;

    #[ORM\Column('label', length: 150)]
    private ?string $name = null;

    /**
     * @var  Collection<int, StreetLocality>
     */
    #[ORM\OneToMany(
        targetEntity: StreetLocality::class,
        mappedBy: 'street',
        orphanRemoval: true,
    )]
    private Collection $streetLocality;

    /**
     * Genre d’objet (rue, place ou autre)
     */
    #[ORM\Column(
        length: 6,
        nullable: true,
        enumType: Type::class,
    )]
    private ?Type $type = null;

    /**
     * État de réalisation de la rue selon le RegBL
     */
    #[ORM\Column(
        /*
         * `status` is a reserved MySQL word:
         * @see https://dev.mysql.com/doc/refman/8.0/en/keywords.html
         */
        name: 'completion_status',
        length: 8,
        enumType: StreetOrAddressStatus::class
    )]
    private ?StreetOrAddressStatus $status = null;

    /**
     * Désignation officielle de la rue. Caractère obligatoire de l’orthographe
     * du nom de la rue selon le RegBL
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isOfficial = null;

    /**
     * Date de la dernière modification de la rue
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $lastModificationDate = null;

    #[Pure]
    public function __construct()
    {
        $this->streetLocality = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    /**
     * @return  int<0, 4294967295>|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param  int<0, 4294967295>|null  $id
     *
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

    public function setType(?Type $type): self
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

    /**
     * @return  Collection<int, StreetLocality>
     */
    public function getStreetLocality(): Collection
    {
        return $this->streetLocality;
    }

    public function getLastModificationDate(): ?\DateTimeImmutable
    {
        return $this->lastModificationDate;
    }

    public function setLastModificationDate(?\DateTimeImmutable $lastModificationDate): self
    {
        $this->lastModificationDate = $lastModificationDate;

        return $this;
    }
}
