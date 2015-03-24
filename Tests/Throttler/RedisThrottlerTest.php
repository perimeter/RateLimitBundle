<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Throttler;

use Perimeter\RateLimitBundle\Throttler\RedisThrottler;
use Perimeter\RateLimitBundle\Tests\ContainerLoader;

class RedisThrottlerTest extends \PHPUnit_Framework_TestCase
{
    public function testConsume()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(array('incrby', 'expireat', 'mget'))
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('incrby')
            ->with('meter:test:10000', 1);
        $client->expects($this->once())
            ->method('expireat')
            ->with('meter:test:10000', 10002);
        $client->expects($this->once())
            ->method('mget')
            ->with('meter:test:10000', 'meter:test:9999')
            ->will($this->returnValue(array(2, null)));

        $config = array(
            'num_buckets'  => 2,
            'bucket_size'  => 1,
            'rate_period'  => 1,
            'track_meters' => false,
        );

        $throttler = new RedisThrottler($client, $config, true);
        $throttler->consume('test', 1, 2, 1, 2, 10000);

        $this->assertFalse($throttler->isLimitWarning());
        $this->assertFalse($throttler->isLimitExceeded());

        ////////////

        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(array('incrby', 'expireat', 'mget'))
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('incrby')
            ->with('meter:test:10001', 1);
        $client->expects($this->once())
            ->method('expireat')
            ->with('meter:test:10001', 10003);
        $client->expects($this->once())
            ->method('mget')
            ->with('meter:test:10001', 'meter:test:10000')
            ->will($this->returnValue(array(2, 2)));

        $throttler = new RedisThrottler($client, $config, true);
        $throttler->consume('test', 1, 2, 1, 2, 10001);

        $this->assertTrue($throttler->isLimitWarning());
        $this->assertFalse($throttler->isLimitExceeded());

        ////////////

        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(array('incrby', 'expireat', 'mget'))
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('incrby')
            ->with('meter:test:10001', 1);
        $client->expects($this->once())
            ->method('expireat')
            ->with('meter:test:10001', 10003);
        $client->expects($this->once())
            ->method('mget')
            ->with('meter:test:10001', 'meter:test:10000')
            ->will($this->returnValue(array(4, 2)));

        $throttler = new RedisThrottler($client, $config, true);
        $throttler->consume('test', 1, 2, 1, 2, 10001);

        $this->assertFalse($throttler->isLimitWarning());
        $this->assertTrue($throttler->isLimitExceeded());
    }

    public function testTrack()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(array('incrby', 'expireat', 'mget', 'hset', 'hgetall'))
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('incrby')
            ->with('meter:test:10000', 1);
        $client->expects($this->exactly(2))
            ->method('expireat')
            ->with($this->logicalOr(
                 $this->equalTo('meter:test:10000'),
                 $this->equalTo('track:10000')
             ), 10002);
        $client->expects($this->once())
            ->method('mget')
            ->with('meter:test:10000', 'meter:test:9999')
            ->will($this->returnValue(array(2, null)));
        $client->expects($this->exactly(2))
            ->method('hgetall')
            ->with($this->logicalOr(
                 $this->equalTo('track:9999'),
                 $this->equalTo('track:10000')
             ))
            ->will($this->returnValue(array('test' => 1)));

        $config = array(
            'num_buckets'  => 2,
            'bucket_size'  => 1,
            'rate_period'  => 1,
            'track_meters' => true,
        );

        $throttler = new RedisThrottler($client, $config, true);
        $throttler->consume('test', 1, 2, 1, 2, 10000);

        $meters = $throttler->getTopMeters(10000);

        $this->assertEquals(1, count($meters));
        $this->assertArrayHasKey('test', $meters);
        $this->assertEquals(2, $meters['test']);
    }

    public function testIntegration()
    {
        if (!$container = ContainerLoader::buildTestContainer()) {
            return $this->markTestSkipped(ContainerLoader::$errorMessage);
        }

        $container->setParameter('kernel.debug', true);

        $throttler = $container->get('perimeter.rate_limit.throttler.redis');
        $meterId1 = sprintf('meter:1:%s', $rand = rand());
        $meterId2 = sprintf('meter:2:%s', $rand);

        $throttler->consume($meterId1, 1, 2, 1, 2);
        $throttler->consume($meterId2, 1, 2, 1, 2);
        $throttler->consume($meterId2, 1, 2, 1, 2);

        $meters = $throttler->getTopMeters();

        $this->assertArrayHasKey($meterId1, $meters);
        $this->assertArrayHasKey($meterId2, $meters);
        $this->assertEquals(1, $meters[$meterId1]);
        $this->assertEquals(2, $meters[$meterId2]);

        // in case this test was run multiple times in the same time block
        foreach ($meters as $key => $meter) {
            if (!in_array($key, array($meterId1, $meterId2))) {
                unset($meters[$key]);
            }
        }

        // ensure sorting is correct
        $meterIds = array_keys($meters);
        $topMeter = array_shift($meterIds);

        $this->assertEquals($meterId2, $topMeter);
    }
}
