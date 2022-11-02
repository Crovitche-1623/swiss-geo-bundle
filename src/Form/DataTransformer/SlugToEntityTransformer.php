<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Form\DataTransformer;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @author  Thibault Gattolliat
 */
class SlugToEntityTransformer implements DataTransformerInterface
{
    /**
     * @param  EntityManagerInterface  $entityManager
     * @param  string  $className
     * @param  QueryBuilder|null  $queryBuilder
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $className,
        private readonly ?QueryBuilder $queryBuilder = null,
    )
    {}

    /**
     * {@inheritDoc}
     *
     * Transforms an object (Customer) to a string (slug).
     */
    public function transform($value): ?string
    {
        if (!$value) {
            return null;
        }

        return method_exists($this->className, 'getSlug') ?
                     $value->getSlug() :
            (string) $value->getId();
    }

    /**
     * {@inheritDoc}
     *
     * Transforms a string (slug) to an object (Customer).
     *
     * @param  mixed  $value  the slug
     *
     * @throws  NonUniqueResultException
     */
    public function reverseTransform(mixed $value): ?object
    {
        if (!$value) {
            return null;
        }

        $identifierColumn = !method_exists($this->className, 'getSlug') ? 'id' : 'slug';

        if (!$this->queryBuilder) {
            $entity = $this->entityManager->getRepository($this->className)
                ->findOneBy([$identifierColumn => $value]);
        } else {
            $entity = $this->queryBuilder
                ->andWhere(
                    sprintf("%s.%s = :identifier",
                        $this->queryBuilder->getRootAliases()[0],
                        $identifierColumn
                    )
                )
                ->setParameter('identifier', $value)
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
        }

        if (!$entity) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'A(n) %s with %s "%s" does not exist!',
                $this->className, $identifierColumn, $value
            ));
        }

        return $entity;
    }
}
