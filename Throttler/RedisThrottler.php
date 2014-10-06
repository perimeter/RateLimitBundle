<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Throttler;

use Predis\ClientInterface;

class RedisThrottler implements ThrottlerInterface
{
    protected $redisClient;
    protected $serverCount;
    protected $bucketSize;
    protected $numBuckets;
    protected $ratePeriod;
    protected $debug;
    protected $redis;
    protected $limitWarning;
    protected $limitExceeded;

    public function __construct(ClientInterface $redisClient, $serverCount, $bucketSize = 60, $numBuckets = 5, $ratePeriod = 3600, $debug = false)
    {
        if ($serverCount < 1) {
            $serverCount = 1;
        }

        if ($bucketSize < 1) {
            $bucketSize = 1;
        }

        if ($numBuckets < 1) {
            $numBuckets = 1;
        }

        if ($ratePeriod < 1) {
            $ratePeriod = 1;
        }

        $this->redisClient = $redisClient;
        $this->serverCount = $serverCount;
        $this->bucketSize = $bucketSize;
        $this->numBuckets = $numBuckets;
        $this->ratePeriod = $ratePeriod;
        $this->debug = $debug;
    }

    public function consume($meterId, $warnThreshold, $limitThreshold, $numTokens = 1, $throttleMilliseconds = 0, $time = null)
    {
        $this->limitWarning = false;
        $this->limitExceeded = false;

        if (is_null($time)) {
            $time = time();
        }

        //Create $numBuckets of $bucketSize seconds
        $buckets[0] = $time - ($time % $this->bucketSize); //Align to $bucketSize second boundaries

        for ($i=1;$i<$this->numBuckets;$i++) {
            $buckets[$i] = $buckets[$i-1] - $this->bucketSize;
        }

        //Build list of redis keys for each bucket
        foreach ($buckets as $bucketStart) {
            $keys[] = sprintf('meter:%s:%d', $meterId, $bucketStart);
        }

        try {
            //Incr current bucket
            $this->redisClient->incrby($keys[0], $numTokens);

            //Expire current bucket at the appropriate time (plus a hashed offset to stagger expirations)
            $this->redisClient->expireat($keys[0], $buckets[0]+($this->bucketSize*$this->numBuckets));

            //Multi-get all buckets
            $rates = call_user_func_array(array($this->redisClient, 'mget'), $keys);

            //Extrapolate rate and account for total number of servers
            $actual = (array_sum($rates)/$this->numBuckets) * ($this->ratePeriod / $this->bucketSize) * $this->serverCount;

            //Check rate against configured limits
            if ($actual > $limitThreshold) {
                $this->limitExceeded = true;
            } elseif ($actual > $warnThreshold) {
                $this->limitWarning = true;
            }

        } catch (\Exception $e) {
            if ($this->debug) {
                throw $e;
            }
        }

        //Induce a delay on calls to this meter
        if ($throttleMilliseconds > 0) {
            usleep($throttleMilliseconds * 1000);
        }
    }

    public function isLimitWarning()
    {
        return $this->limitWarning;
    }

    public function isLimitExceeded()
    {
        return $this->limitExceeded;
    }
}
