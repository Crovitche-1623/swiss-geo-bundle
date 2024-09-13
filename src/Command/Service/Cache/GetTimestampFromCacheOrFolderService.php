<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\Cache;

use Crovitche\SwissGeoBundle\Command\Service\Exception\LocalTimestampMoreRecentException;
use Crovitche\SwissGeoBundle\Command\Service\ZipArchive\SysGetTempDir;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\{CacheInterface, ItemInterface};

/**
 * Responsible for throwing an exception if the timestamp in file is not newer
 * than the one in cache
 */
class GetTimestampFromCacheOrFolderService
{
    private const DEFAULT_TIMESTAMP_FILENAME = 'timestamp.txt';
    private const TIMESTAMP_FORMAT_REGEX = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/';

    private bool $timestampDidNotExistLocally = false;

    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @throws  InvalidArgumentException
     * @throws  LocalTimestampMoreRecentException
     *
     * @return  string  The timestamp from file
     */
    public function __invoke(
        OutputStyle $io,
        string $cacheKeyName,
        string|\Stringable|SysGetTempDir $directory = new SysGetTempDir(),
        string $filename = self::DEFAULT_TIMESTAMP_FILENAME,
        string $timestampRegex = self::TIMESTAMP_FORMAT_REGEX,
    ): string {
        $finder = (new Finder())->files()->in((string) $directory)
            ->name($filename)
            ->contains($timestampRegex);

        if (!$finder->hasResults()) {
            // Log something here instead of throwing an exception
            // throw new FileNotFoundException("There is no file called `$filename` containing a timestamp with this format (YYYY-MM-DD) in `$directory` directory.");
            
            return (new \DatetimeImmutable())->format('Y-m-d');
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
                "No locally existing timestamp $cacheKeyName. The received ".
                "($timestampFromFile) will be used..."
            );

            return $timestampFromFile;
        }

        if ($timestampFromCache >= $timestampFromFile) {
            throw new LocalTimestampMoreRecentException($cacheKeyName, $timestampFromCache, $timestampFromFile);
        }

        $io->note(
            "There was a local timestamp indicating that the $cacheKeyName had ".
            "already been imported (timestamp $timestampFromCache). However, ".
            "the one received is more recent (timestamp $timestampFromFile). ".
            'The import continues.'
        );

        return $timestampFromFile;
    }
}
