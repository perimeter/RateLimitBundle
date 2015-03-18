<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testContainerServices()
    {
        // build container from services.xml
        if (!$container = ContainerLoader::buildTestContainer()) {
            return $this->markTestSkipped(ContainerLoader::$errorMessage);
        }

        // mock parameters that should be there from symfony but arent
        $container->setParameter('kernel.debug', true);
        $container->set('templating', $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface'));
        $container->set('security.context', $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface'));

        $container->get('perimeter.rate_limit.throttler');
        $container->get('perimeter.rate_limit.redis_client');
        $container->get('perimeter.rate_limit.throttler.redis');
        $container->get('perimeter.rate_limit.throttler.doctrine');
        $container->get('perimeter.rate_limit.meter_api_controller');
        $container->get('perimeter.rate_limit.over_limit_controller');
        $container->get('perimeter.rate_limit.storage');
        $container->get('perimeter.rate_limit.storage.doctrine');
        $container->get('perimeter.rate_limit.storage.memory');
        $container->get('perimeter.rate_limit.storage.admin');
        $container->get('perimeter.rate_limit.meter_resolver');
        $container->get('perimeter.rate_limit.listener');
    }
}
