<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle;

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
        $loader = new YamlFileLoader($builder, new FileLocator(dirname(__DIR__).'/config'));
        $loader->load('services.yaml');
    }
}
