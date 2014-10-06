<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Meter API Controller
 *
 * @package    Perimeter
 * @subpackage RateLimitBundle
 * @author     Brent Shaffer <bshaffer@adobe.com>
 */
class OverLimitController
{
    protected $templating;

    public function __construct(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    public function overLimitAction(Request $request, $meter = array())
    {
        $content = null;

        if (in_array($format = $request->get('_format', 'json'), array('json', 'xml'))) {
            $content = $this->templating->render(
                sprintf('PerimeterRateLimitBundle:OverLimit:over_limit.%s.twig', $format),
                array('meters' => $meter)
            );
        }

        return new Response($content, Response::HTTP_TOO_MANY_REQUESTS);
    }
}
