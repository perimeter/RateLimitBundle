<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Entity;

use Perimeter\RateLimitBundle\Tests\ContainerLoader;
use Perimeter\RateLimitBundle\Entity\RateLimitBucket;

class RateLimitBucketTest extends \PHPUnit_Framework_TestCase
{
    public function testPersist()
    {
        if (!$container = ContainerLoader::buildTestContainer()) {
            return $this->markTestSkipped(ContainerLoader::$errorMessage);
        }

        $em = $container->get('doctrine.orm.entity_manager');

        $bucket = new RateLimitBucket();
        $bucket->meter_id   = $meterId = 'meter-id-'.mt_rand();
        $bucket->time_block = $timeBlock = strtotime('-10 minutes'); // ten minutes from now

        $em->persist($bucket);
        $em->flush();

        $stored = $em->getRepository('Perimeter\RateLimitBundle\Entity\RateLimitBucket')
            ->findOneBy(array('meter_id' => $meterId, 'time_block' => $timeBlock));
    }
}
