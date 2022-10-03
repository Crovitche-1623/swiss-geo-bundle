<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Import\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 */
class ImportingException extends \RuntimeException
{
    #[Pure]
    public function __construct(readonly string $subject)
    {
        parent::__construct(
            "Cannot import \"$subject\". Contact an administrator to
            investigate."
        );
    }
}
