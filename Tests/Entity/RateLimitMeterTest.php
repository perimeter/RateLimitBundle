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
use Perimeter\RateLimitBundle\Entity\RateLimitMeter;

class RateLimitMeterTest extends \PHPUnit_Framework_TestCase
{
    public function testPersist()
    {
        if (!$container = ContainerLoader::buildTestContainer()) {
            return $this->markTestSkipped(ContainerLoader::$errorMessage);
        }

        $em = $container->get('doctrine.orm.entity_manager');

        $meter = new RateLimitMeter();
        $meter->meter_id = $meterId = 'meter-id-'.mt_rand();
        $meter->warn_threshold  = 0;
        $meter->limit_threshold = 10;

        $em->persist($meter);
        $em->flush();

        $stored = $em->getRepository('Perimeter\RateLimitBundle\Entity\RateLimitMeter')
            ->findOneBy(array('meter_id' => $meterId));

        $this->assertNotNull($stored);
        $this->assertEquals($meterId, $stored->meter_id);
        $this->assertEquals(0, $stored->warn_threshold);
        $this->assertEquals(10, $stored->limit_threshold);
    }
}
