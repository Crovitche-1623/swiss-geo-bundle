<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\ZipArchive\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author  Thibault Gattolliat
 */
class CopyingFileException extends \RuntimeException
{
    #[Pure]
    public function __construct(
        readonly string $from,
        readonly string $to,
        readonly ?int $httpStatusCode = null
    ) {
        $message = "Cannot copy \"$from\" to \"$to\".".\PHP_EOL;

        if ($httpStatusCode) {
            $message .= "Server responded with a $httpStatusCode code".\PHP_EOL;
        } else {
            $message .= 'Probably a permission issue. Can you overwrite the file
             manually ?'.\PHP_EOL;
        }

        parent::__construct($message);
    }
}
