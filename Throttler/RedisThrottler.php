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

class RedisThrottler implements ThrottlerInterface, ThrottlerAdminInterface
{
    protected $redis;
    protected $config;
    protected $debug;
    protected $limitWarning;
    protected $limitExceeded;

    public function __construct(ClientInterface $redis, $config = array(), $debug = false)
    {
        $this->config = array_merge(array(
            'server_count' => 1,
            'num_buckets'  => 5,
            'bucket_size'  => 60,
            'rate_period'  => 3600,
            'track_meters' => true,
        ), $config);

        $this->redis = $redis;
        $this->debug = $debug;
    }

    public function consume($meterId, $warnThreshold, $limitThreshold, $numTokens = 1, $throttleMilliseconds = 0, $time = null)
    {
        $this->limitWarning = false;
        $this->limitExceeded = false;

        $buckets = $this->getBuckets($time);

        //Build list of redis keys for each bucket
        foreach ($buckets as $bucketStart) {
            $keys[] = sprintf('meter:%s:%d', $meterId, $bucketStart);
        }

        try {
            //Incr current bucket
            $this->redis->incrby($keys[0], $numTokens);

            //Expire current bucket at the appropriate time (plus a hashed offset to stagger expirations)
            $expireAt = $buckets[0] + ($this->config['bucket_size'] * $this->config['num_buckets']);
            $this->redis->expireat($keys[0], $expireAt);

            //Multi-get all buckets
            $rates = call_user_func_array(array($this->redis, 'mget'), $keys);

            //Extrapolate rate and account for total number of servers
            $actual = (array_sum($rates) / $this->config['num_buckets']) * ($this->config['rate_period'] / $this->config['bucket_size']) * $this->config['server_count'];

            if ($this->config['track_meters']) {
                $this->trackMeter($meterId, $buckets, $rates);
            }

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

    public function getTopMeters($time = null, $numMeters = null)
    {
        $buckets = $this->getBuckets($time);
        $totals = array();

        foreach ($buckets as $i => $bucket) {
            $trackKey = sprintf('track:%s', $bucket);
            $meters = $this->redis->hgetall($trackKey);

            foreach ($meters as $meterId => $tokens) {
                if (!isset($totals[$meterId])) {
                    $totals[$meterId] = 0;
                }

                $totals[$meterId] += $tokens;
            }
        }

        // do we want to do this in PHP?
        asort($totals, SORT_NUMERIC);

        return array_reverse($totals);
    }

    public function getTokenRate($numMinutes = null)
    {
        $meters = $this->getTopMeters();

        return array_sum($this->getTopMeters());
    }

    /**
     * This method allows you to change how the meter is tracked
     * So if you'd like to use a sorted set rather than a hash,
     * go on ahead!
     */
    protected function trackMeter($meterId, $buckets, $rates)
    {
        // create a key for this bucket's start time
        $trackKey = sprintf('track:%s', $buckets[0]);

        // track the meter key to this bucket with the number of times it was called
        $this->redis->hset($trackKey, $meterId, $rates[0]);

        // ensure this meter expires
        $expireAt = $buckets[0] + ($this->config['bucket_size'] * $this->config['num_buckets']);
        $this->redis->expireat($trackKey, $expireAt);
    }

    private function getBuckets($time = null)
    {
        $buckets = array();

        if (is_null($time)) {
            $time = time();
        }

        //Create $config['num_buckets'] of $config['bucket_size'] seconds
        $buckets[0] = $time - ($time % $this->config['bucket_size']); //Align to $config['bucket_size'] second boundaries

        for ($i=1; $i < $this->config['num_buckets']; $i++) {
            $buckets[$i] = $buckets[$i-1] - $this->config['bucket_size'];
        }

        return $buckets;
    }
}
