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
 * @ORM\Table(name="rate_limit_meter",uniqueConstraints={@ORM\UniqueConstraint(name="meter_id_idx", columns={"meter_id"})})
 */
class RateLimitMeter
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     **/
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     **/
    public $meter_id;

    /**
     * @ORM\Column(type="integer")
     **/
    public $warn_threshold;

    /**
     * @ORM\Column(type="integer")
     **/
    public $limit_threshold;

    /**
     * @ORM\Column(type="boolean")
     **/
    public $should_warn = true;

    /**
     * @ORM\Column(type="boolean")
     **/
    public $should_limit = false;

    /**
     * @ORM\Column(type="boolean")
     **/
    public $num_tokens = 1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     **/
    public $throttle_ms = 0;

    public function getId()
    {
        return $this->id;
    }

    public function fromArray($array)
    {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }
    }

    public function toArray()
    {
        return array(
            'id'              => $this->id,
            'meter_id'        => $this->meter_id,
            'warn_threshold'  => $this->warn_threshold,
            'limit_threshold' => $this->limit_threshold,
            'should_warn'     => $this->should_warn,
            'should_limit'    => $this->should_limit,
            'num_tokens'      => $this->num_tokens,
            'throttle_ms'     => $this->throttle_ms,
        );
    }
}
