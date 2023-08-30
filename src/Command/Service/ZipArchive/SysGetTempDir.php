<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\ZipArchive;

/**
 * This class is a workaround because we cannot use expression as default value
 * for constructor. It was needed for:
 *
 * __construct(string $parameter = sys_get_temp_dir()) {
 *
 * }
 */
class SysGetTempDir implements \Stringable
{
    /**
     * @static
     */
    public function __toString(): string
    {
        return \sys_get_temp_dir();
    }
}
