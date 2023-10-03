<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 */
class ClosingException extends \RuntimeException
{
    #[Pure]
    public function __construct(
        readonly string $from,
        ?\Throwable $previous = null,
    )
    {
        parent::__construct(
            "Cannot close the zip archive \"$from\" normally opened previously",
            previous: $previous,
        );
    }
}
