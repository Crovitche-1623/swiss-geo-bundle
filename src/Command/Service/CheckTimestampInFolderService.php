<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service;

use Crovitche\SwissGeoBundle\Command\Service\Exception\LocalTimestampMoreRecentException;
use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\SysGetTempDir;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Responsible for throwing an exception if the timestamp in file is not newer
 * than the one in cache
 */
class CheckTimestampInFolderService
{
    private const DEFAULT_TIMESTAMP_FILENAME = 'timestamp.txt';
    private const TIMESTAMP_FORMAT_REGEX = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/';

    private bool $timestampDidNotExistLocally = false;

    public function __construct(
        private readonly CacheInterface $cache
    )
    {}

    /**
     * @throws  InvalidArgumentException
     * @throws  LocalTimestampMoreRecentException
     */
    public function __invoke(
        OutputStyle $io,
        string $cacheKeyName,
        string|\Stringable|SysGetTempDir $directory = new SysGetTempDir(),
        string $filename = self::DEFAULT_TIMESTAMP_FILENAME,
        string $timestampRegex = self::TIMESTAMP_FORMAT_REGEX,
    ): void
    {
        $finder = (new Finder)->files()->in((string) $directory)
            ->name($filename)
            ->contains($timestampRegex);

        if (!$finder->hasResults()) {
            throw new FileNotFoundException(
                "There is no file called `$filename` containing a timestamp " .
                "with this format (YYYY-MM-DD) in `$directory` directory."
            );
        }

        $timestampFromFile = null;
        foreach ($finder as $file) {
            $timestampFromFile = $file->getContents();
        }

        $timestampFromCache = $this->cache->get(
            $cacheKeyName,
            function (ItemInterface $item) use ($timestampFromFile): string {
                $item->expiresAfter(null);

                $this->timestampDidNotExistLocally = true;

                return $timestampFromFile;
            }
        );

        if ($this->timestampDidNotExistLocally) {
            $io->info(
                "No locally existing timestamp $cacheKeyName. The received " .
                "($timestampFromFile) has been written..."
            );

            return;
        }

        if ($timestampFromCache >= $timestampFromFile) {
            throw new LocalTimestampMoreRecentException($cacheKeyName, $timestampFromCache, $timestampFromFile);
        }

        $io->note(
            "There was a local timestamp indicating that the $cacheKeyName had ".
            "already been imported (timestamp $timestampFromCache). However, " .
            "the one received is more recent (timestamp $timestampFromFile). " .
            "The import continues."
        );

         // Invalidate cache and recreate it with new timestamp
        $this->cache->delete($cacheKeyName);
        $this->cache->get(
            $cacheKeyName,
            static function (ItemInterface $item)
            use ($timestampFromFile): string {
                $item->expiresAfter(null);
                return $timestampFromFile;
            }
        );
    }
}
