<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Enum\Street;

/**
 * @author  Thibault Gattolliat
 *
 * Areas and place are also considered as street in Switzerland Topography
 * system
 */
enum Type: string
{
    case Area = 'Area';
    case Street = 'Street';
    case Place = 'Place';
}
