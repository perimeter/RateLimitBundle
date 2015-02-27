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

        $throttler = new RedisThrottler($client, 1, 2, 1, 1, true);
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

        $throttler = new RedisThrottler($client, 1, 2, 1, 1, true);
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

        $throttler = new RedisThrottler($client, 1, 2, 1, 1, true);
        $throttler->consume('test', 1, 2, 1, 2, 10001);

        $this->assertFalse($throttler->isLimitWarning());
        $this->assertTrue($throttler->isLimitExceeded());
    }
}
