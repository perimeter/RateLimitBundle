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

interface MeterStorageInterface
{
    const DEFAULT_METER_ID = '::DEFAULT::';

    public function getMeter($meterId, $meterType = null);
}
