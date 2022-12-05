<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author  Thibault Gattolliat
 */
#[ORM\MappedSuperclass]
abstract class AbstractEntity
{
    /**
     * @var  null|int<0, 4294967295>
     */
    #[ORM\Id, ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ["unsigned" => true])]
    protected ?int $id = null;

    /**
     * @return  null|int<0, 4294967295>
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param  null|int<0, 4294967295>  $id
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
}
