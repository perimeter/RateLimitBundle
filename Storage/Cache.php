<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Storage;

use Perimeter\CacheBundle\Cache\CacheServiceInterface;

class Cache implements MeterStorageInterface
{
    protected $cacheService;
    protected $meterStorage;
    protected $cacheExpires;

    public function __construct(CacheServiceInterface $cacheService, MeterStorageInterface $meterStorage, $cacheExpires = 500)
    {
        $this->cacheService = $cacheService;
        $this->meterStorage = $meterStorage;
        $this->cacheExpires = $cacheExpires;
    }

    /**
     * Gets the pulsar meter from cache or db
     */
    public function getMeter($meterIdentifier)
    {
        // create cache key
        $item_key = $this->getCacheKey($meterIdentifier);

        // if not cached, go get it
        if (!($meter = $this->cacheService->retrieve($item_key))) {
            try {
                // find specific meter for company and login
                $meter = $this->meterStorage->getMeter($meterIdentifier);
            } catch (\Exception $e) {
                $meter = array();
            }

            // cache meter
            $this->cacheService->store($item_key, $meter, $this->cacheExpires);
        }

        return $meter;
    }

    protected function getCacheKey($meterIdentifier)
    {
        return sprintf('perimeter.rate_limit:%s', $meterIdentifier);
    }
}
