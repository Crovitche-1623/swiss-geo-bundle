<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 */
class OpeningException extends \RuntimeException
{
    #[Pure]
    public function __construct(
        readonly string $zipArchive,
        ?\Throwable $previous = null,
    )
    {
        parent::__construct(
            "The zip archive \"$zipArchive\" cannot be opened... Maybe a permission issue ?",
            previous: $previous,
        );
    }
}
