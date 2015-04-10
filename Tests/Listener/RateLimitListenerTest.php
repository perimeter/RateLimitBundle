<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Listener;

use Perimeter\RateLimitBundle\Listener\RateLimitListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RateLimitListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnKernelRequest()
    {
        // create a mock resolver
        $resolver = $this->getMock('Perimeter\RateLimiter\Resolver\MeterResolverInterface');
        $resolver->expects($this->any())
             ->method('getMeterIdentifier')
             ->will($this->returnValue('test'));

        // create mock storage service that will return a test meter
        $storage = $this->getMockBuilder('Perimeter\RateLimiter\Storage\MeterStorageInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $storage->expects($this->any())
             ->method('getMeter')
             ->will($this->returnValue(array(
                'meter_id'        => 'test',
                'warn_threshold'  => 1,
                'limit_threshold' => 1,
                'should_warn'     => true,
                'should_limit'    => true,
                'num_tokens'      => 1,
                'throttle_ms'     => 0,
             )));

        // create mock twig service to render template
        $controller = $this->getMockBuilder('Perimeter\RateLimitBundle\Controller\OverLimitController')
            ->disableOriginalConstructor()
            ->getMock();
        $controller->expects($this->any())
             ->method('overLimitAction')
             ->will($this->returnValue(new Response('{"error":"request over limit"}')));

        // create mock throttler
        $throttler = $this->getMockBuilder('Perimeter\RateLimiter\Throttler\ThrottlerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $throttler->expects($this->once())
            ->method('consume');
        $throttler->expects($this->once())
            ->method('isLimitExceeded')
            ->will($this->returnValue(true));

        $listener = new RateLimitListener($throttler, $resolver, $storage, $controller, true);

        // create a mock kernel for the kernel event
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock();

        $request = new Request();
        $event   = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        // test maintenance response
        $listener->onKernelRequest($event);
        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();
        $this->assertEquals('{"error":"request over limit"}', $response->getContent());
    }
}
