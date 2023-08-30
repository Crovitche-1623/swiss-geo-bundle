<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Command\Service\Cache;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\{CacheInterface, ItemInterface};

class WriteCacheWithTimestampService
{
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    /**
     * @throws  InvalidArgumentException   when $cacheKeyName is not valid
     */
    public function __invoke(
        string $cacheKeyName,
        string $timestamp
    ): void {
        // Invalidate cache and recreate it with new timestamp
        $this->cache->delete($cacheKeyName);

        $this->cache->get(
            $cacheKeyName,
            static function (ItemInterface $item) use ($timestamp): string {
                $item->expiresAfter(null);

                return $timestamp;
            }
        );
    }
}
