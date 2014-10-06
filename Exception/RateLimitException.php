<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Exception;

class RateLimitException extends \Exception
{
    private $meter_id;

    public function __construct($meter_id, $message = '', $code = 0, \Exception $previous = null)
    {
        $this->meter_id = $meter_id;

        parent::__construct($message, $code, $previous);
    }
}
