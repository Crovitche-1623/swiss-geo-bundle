<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Repository;

/**
 * @author  Thibault Gattolliat
 */
class RegionRepository
{
    private const REGION_LIMIT = 7;

    public function findAll(
        ?string $regionAbbreviation = null,
        ?int $numberOfRegions = self::REGION_LIMIT
    ): array {
        $regions = self::getRegions();

        if ($regionAbbreviation) {
            $regions = array_filter(
                $regions,
                static fn (string $key): bool =>
                    str_contains($key, $regionAbbreviation)
            );
        }

        if (!$numberOfRegions) {
            return $regions;
        }

        return array_slice(
            array: $regions,
            offset: 0,
            length: $numberOfRegions,
            preserve_keys: true
        );
    }

    /**
     * @return  array<string, string>
     */
    public static function getRegions(): array
    {
        return [
            'AR' => 'Appenzell Rhodes-Extérieures',
            'AI' => 'Appenzell Rhodes-Intérieures',
            'AG' => 'Argovie',
            'BL' => 'Bâle-Campagne',
            'BS' => 'Bâle-Ville',
            'BE' => 'Berne',
            'FR' => 'Fribourg',
            'GE' => 'Genève',
            'GL' => 'Glaris',
            'GR' => 'Grisons',
            'JU' => 'Jura',
            'LU' => 'Lucerne',
            'NE' => 'Neuchâtel',
            'NW' => 'Nidwald',
            'OW' => 'Obwald',
            'SG' => 'Saint-Gall',
            'SH' => 'Schaffhouse',
            'SZ' => 'Schwytz',
            'SO' => 'Soleure',
            'TI' => 'Tessin',
            'TG' => 'Thurgovie',
            'UR' => 'Uri' ,
            'VD' => 'Vaud',
            'VS' => 'Valais',
            'ZG' => 'Zoug',
            'ZH' => 'Zurich'
        ];
    }
}
