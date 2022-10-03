<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author  Thibault Gattolliat
 */
class CrovitcheSwissGeoBundle extends AbstractBundle
{
    /**
     * {@inheritDoc}
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()

            ->end()
        ;
    }
}
