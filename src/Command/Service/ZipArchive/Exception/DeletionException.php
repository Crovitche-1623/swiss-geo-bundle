<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 */
class DeletionException extends \RuntimeException
{
    #[Pure]
    public function __construct(
        readonly string $from,
        ?\Throwable $previous = null
    )
    {
        parent::__construct(
            "Cannot delete \"$from\". Maybe a permission issue ?",
            previous: $previous,
        );
    }
}
