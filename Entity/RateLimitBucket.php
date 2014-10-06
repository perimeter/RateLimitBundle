<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RateLimitMeter
 * 
 * Doctrine2 Entity for RateLimit Meters
 *
 * @ORM\Entity
 * @ORM\Table(name="rate_limit_bucket",uniqueConstraints={@ORM\UniqueConstraint(name="meter_id_time_block_idx", columns={"meter_id", "time_block"})})
 */
class RateLimitBucket
{
    /**
     * @ORM\Column(type="string", length=100)
     * @ORM\Id
     **/
    public $meter_id;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     **/
    public $time_block;

    /**
     * @ORM\Column(type="integer")
     **/
    public $tokens = 0;
}
