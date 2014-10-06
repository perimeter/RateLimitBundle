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

interface MeterStorageAdminInterface
{
    public function addMeter(array $meterData);
    public function deleteMeter($meterId);
    public function saveMeterData(array $meterData);

    public function findOneByMeterId($meterId, $meterType = null);
    public function findAll($limit, $page);
    public function findBySearch($search, $limit, $page);
}
