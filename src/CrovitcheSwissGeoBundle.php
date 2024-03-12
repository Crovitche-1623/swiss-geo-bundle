<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author  Thibault Gattolliat
 */
class CrovitcheSwissGeoBundle extends AbstractBundle
{
    private const LOCALITIES_URL = 'https://data.geo.admin.ch/ch.swisstopo-vd.ortschaftenverzeichnis_plz/ortschaftenverzeichnis_plz/ortschaftenverzeichnis_plz_2056.csv.zip';
    private const STREETS_URL = 'https://data.geo.admin.ch/ch.swisstopo.amtliches-strassenverzeichnis/csv/2056/ch.swisstopo.amtliches-strassenverzeichnis.zip';
    private const BUILDING_ADDRESSES_URL = 'https://data.geo.admin.ch/ch.swisstopo.amtliches-gebaeudeadressverzeichnis/csv/2056/ch.swisstopo.amtliches-gebaeudeadressverzeichnis.zip';
    private const MEILISEARCH_URL = 'http://localhost:7071';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('import')
                    ->children()
                        ->scalarNode('localities_url')
                            ->defaultValue(self::LOCALITIES_URL)
                        ->end()
                        ?->scalarNode('streets_url')
                            ->defaultValue(self::STREETS_URL)
                        ->end()
                        ?->scalarNode('building_addresses_url')
                            ->defaultValue(self::BUILDING_ADDRESSES_URL)
                        ->end()
                    ?->end()
                ->end()
                ->scalarNode('meilisearch_url')
                    ->isRequired()
                    ->defaultValue(self::MEILISEARCH_URL)
                ->end()
            ?->end()
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $loader = new YamlFileLoader($builder, new FileLocator(\dirname(__DIR__).'/config'));
        $loader->load('services.yaml');

        $builder->setParameter('crovitche_swiss_geo.meilisearch_url', $config['meilisearch_url']);
        $builder->setParameter('crovitche_swiss_geo.import.localities_url', $config['import']['localities_url']);
        $builder->setParameter('crovitche_swiss_geo.import.streets_url', $config['import']['streets_url']);
        $builder->setParameter('crovitche_swiss_geo.import.building_addresses_url', $config['import']['building_addresses_url']);
    }
}
