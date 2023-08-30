<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Form\DataTransformer;

use Crovitche\SwissGeoBundle\Repository\RegionRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @author  Thibault Gattolliat
 */
class RegionAbbreviationToRegionTransformer implements DataTransformerInterface
{
    /**
     * {@inheritDoc}
     *
     * Transforms a region to an abbreviation
     *
     * @static
     */
    public function transform($value): string
    {
        $abbreviation = \array_search($value, RegionRepository::getRegions(), true);

        if (false === $abbreviation) {
            throw new TransformationFailedException(\sprintf('Cannot find abbreviation for region %s.', $value));
        }

        return $abbreviation;
    }

    /**
     * {@inheritDoc}
     *
     * Transforms an abbreviation to region
     *
     * @static
     */
    public function reverseTransform($value): string
    {
        if (!\array_key_exists($value, RegionRepository::getRegions())) {
            throw new TransformationFailedException(\sprintf('Cannot find region for abbreviation %s', $value));
        }

        return RegionRepository::getRegions()[$value];
    }
}
