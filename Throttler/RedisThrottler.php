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
        ), $config);

        $this->redis = $redis;
        $this->debug = $debug;
    }

    public function consume($meterId, $warnThreshold, $limitThreshold, $numTokens = 1, $throttleMilliseconds = 0, $time = null)
    {
        $this->limitWarning = false;
        $this->limitExceeded = false;

        if (is_null($time)) {
            $time = time();
        }

        //Create $config['num_buckets'] of $config['bucket_size'] seconds
        $buckets[0] = $time - ($time % $this->config['bucket_size']); //Align to $config['bucket_size'] second boundaries

        for ($i=1; $i < $this->config['num_buckets']; $i++) {
            $buckets[$i] = $buckets[$i-1] - $this->config['bucket_size'];
        }

        //Build list of redis keys for each bucket
        foreach ($buckets as $bucketStart) {
            $keys[] = sprintf('meter:%s:%d', $meterId, $bucketStart);
        }

        try {
            //Incr current bucket
            $this->redis->incrby($keys[0], $numTokens);

            //Expire current bucket at the appropriate time (plus a hashed offset to stagger expirations)
            $this->redis->expireat($keys[0], $buckets[0] + ($this->config['bucket_size'] * $this->config['num_buckets']));

            //Multi-get all buckets
            $rates = call_user_func_array(array($this->redis, 'mget'), $keys);

            //Extrapolate rate and account for total number of servers
            $actual = (array_sum($rates) / $this->config['num_buckets']) * ($this->config['rate_period'] / $this->config['bucket_size']) * $this->config['server_count'];

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
