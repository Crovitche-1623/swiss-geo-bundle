<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\Exception;

use JetBrains\PhpStorm\Pure;

class LocalTimestampMoreRecentException extends \Exception {

    #[Pure]
    public function __construct(
        readonly string $subject,
        readonly string $localTimestamp,
        readonly string $remoteTimestamp,
        int $code = 422,
        \Throwable $previous = null
    )
    {
        $message =
            "$subject import is stopped here because the local timestamp " .
            "($localTimestamp) is the same or more recent than the one " .
            "received ($remoteTimestamp)."
        ;

        parent::__construct($message, $code, $previous);
    }
}
