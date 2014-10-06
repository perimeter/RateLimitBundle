<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Resolver;

use Perimeter\RateLimitBundle\Resolver\SecurityContextMeterResolver;

class SecurityContextMeterResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMeterIdentifier()
    {
        // create a mock token
        $username = 'somelogin:test company '.rand();
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())
             ->method('getUsername')
             ->will($this->returnValue($username));

        // create a mock security context for the token
        $context = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->any())
             ->method('getToken')
             ->will($this->returnValue($token));

        $resolver = new SecurityContextMeterResolver($context);

        $this->assertEquals($username, $resolver->getMeterIdentifier());
    }
}
