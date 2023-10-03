<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 */
class DirectoryException extends \RuntimeException
{
    #[Pure]
    public function __construct(
        readonly string $to,
        ?\Throwable $previous = null,
    )
    {
        parent::__construct(
            "\"$to\" is not a directory or it could not be created",
            previous: $previous,
        );
    }
}
