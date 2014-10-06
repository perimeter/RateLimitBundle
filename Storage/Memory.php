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

class Memory implements MeterStorageInterface
{
    protected $meters;

    public function __construct(array $meters)
    {
        $this->meters = $meters;
    }

    public function getMeter($meterId, $meterType = null)
    {
        // $meterType is only used for MultipleMeterStorage, which is not supported in the Memory storage
        if (!is_null($meterType)) {
            throw new \InvalidArgumentException('Multiple Meter Resolution is not supported with Memory storage');
        }

        if (isset($this->meters[$meterId])) {
            // we index by meter_id, but we want to ensure we return this as part of the meter
            $meter = $this->meters[$meterId];
            $meter['meter_id'] = $meterId;

            return $meter;
        }

        if (isset($this->meters[self::DEFAULT_METER_ID])) {
            // we index by meter_id, but we want to ensure we return this as part of the meter
            $meter = $this->meters[self::DEFAULT_METER_ID];
            $meter['meter_id'] = self::DEFAULT_METER_ID;

            return $meter;
        }

        throw new \Exception(sprintf('No default meter found - please create a default meter with the name "%s"', self::DEFAULT_METER_ID));
    }
}
