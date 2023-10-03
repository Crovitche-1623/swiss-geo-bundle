<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Import\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 */
class InternalImportingException extends \RuntimeException
{
    #[Pure]
    public function __construct(
        readonly string $subject,
        ?\Throwable $previous = null
    )
    {
        parent::__construct(
            "Cannot import \"$subject\". Contact an administrator to investigate.",
            previous: $previous,
        );
    }
}
