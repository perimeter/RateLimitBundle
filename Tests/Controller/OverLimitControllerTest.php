<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Controller;

use Perimeter\RateLimitBundle\Controller\OverLimitController;
use Symfony\Component\HttpFoundation\Request;

class OverLimitControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testOverLimitAction()
    {
        // create mock twig service to render template
        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnCallback(function($template, $variables) { return $template; }));

        $controller = new OverLimitController($templating);

        // test template defaults to json when no format is set
        $request = new Request;
        $this->assertEquals('PerimeterRateLimitBundle:OverLimit:over_limit.json.twig', $controller->overLimitAction($request)->getContent());

        // test template format when content type is xml
        $request = new Request;
        $request->attributes->set('_format', 'xml');
        $this->assertEquals('PerimeterRateLimitBundle:OverLimit:over_limit.xml.twig', $controller->overLimitAction($request)->getContent());

        // content is empty when a template doesn't exist for that format
        $request = new Request;
        $request->attributes->set('_format', 'html');
        $this->assertEquals('', $controller->overLimitAction($request)->getContent());
    }
}