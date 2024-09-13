<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Enum;

/**
 * @author  Thibault Gattolliat
 */
enum StreetOrAddressStatus: string
{
    case Planned = 'planned';
    case Existing = 'real';
    case Outdated = 'outdated';
}
