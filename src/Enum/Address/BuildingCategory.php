<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Enum\Address;

/**
 * @author  Thibault Gattolliat
 */
enum BuildingCategory: string
{
  case Temporary = "temporary";
  case Residential = "residential";
  case OtherResidential = "other_residential";
  case PartlyResidential = "partly_residential";
  case NonResidential = "non_residential";
  case Special = "special";
  case Uncategorized = 'uncategorized';
}
