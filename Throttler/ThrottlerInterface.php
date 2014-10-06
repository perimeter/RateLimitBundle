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

interface ThrottlerInterface
{
    public function consume($meterId, $warnThreshold, $rateThreshold, $numTokens = 1, $throttleMilliseconds = 0, $time = null);
    public function isLimitWarning();
    public function isLimitExceeded();
}
