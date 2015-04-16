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

use Perimeter\RateLimiter\Storage\MeterStorageAdminInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Meter API Controller
 *
 * @package    Perimeter
 * @subpackage RateLimitBundle
 * @author     Brent Shaffer <bshaffer@adobe.com>
 */
class MeterApiController extends Controller
{
    protected $meterStorage;
    protected $templating;
    protected $responseClass;

    public function __construct(MeterStorageAdminInterface $meterStorage, EngineInterface $templating, $responseClass = 'Symfony\Component\HttpFoundation\Response')
    {
        $this->meterStorage  = $meterStorage;
        $this->templating    = $templating;
        $this->responseClass = $responseClass;
    }

    public function getAction(Request $request)
    {
        $limit = $request->get('limit', 10);
        $page  = $request->get('page', 1);

        if ($meterId = $request->get('meter_id')) {
            $meter  = $this->meterStorage->findOneByMeterId($meterId);
            $meters = $meter ? array($meter) : array(); // stay consistent
        } elseif ($search = $request->get('search')) {
            $meters = $this->meterStorage->findBySearch($search, $limit, $page);
        } else {
            $meters = $this->meterStorage->findAll($limit, $page);
        }

        $content = $this->templating->render(
            sprintf('PerimeterRateLimitBundle:MeterApi:get.%s.twig', $request->get('_format')),
            array('meters' => $meters)
        );

        return $this->createResponseObject($content);
    }

    public function postAction(Request $request)
    {
        $format = $request->get('_format');

        if (!$meterId = $request->get('meter_id')) {
            return $this->createErrorResponse('meter_id is a required parameter', $format);
        }

        if ($this->meterStorage->findOneByMeterId($meterId)) {
            return $this->createErrorResponse('a meter already exists for this meter_id', $format);
        }

        if (!$limit_threshold = $request->get('limit_threshold')) {
            return $this->createErrorResponse('limit_threshold is a required parameter', $format);
        }

        $meterData = array_filter(array(
            'meter_id'     => $meterId,
            'num_tokens'   => $request->get('num_tokens'),
            'should_warn'  => $request->get('should_warn'),
            'should_limit' => $request->get('should_limit'),
            'limit_threshold' => $limit_threshold,
            'warn_threshold'  => $request->get('warn_threshold'),
            'throttle_ms'  => $request->get('throttle_ms'),
        ), 'self::filterNullValues');

        if (!$meter = $this->meterStorage->addMeter($meterData)) {
            return $this->createErrorResponse('Unable to save meter', $format);
        }

        $content = $this->templating->render(
            sprintf('PerimeterRateLimitBundle:MeterApi:post.%s.twig', $format),
            array('meter' => $meter)
        );

        return $this->createResponseObject($content, 201); // Status Code: 201 Created
    }

    public function putAction(Request $request)
    {
        $format = $request->get('_format');

        if (!$meterId = $request->get('meter_id')) {
            return $this->createErrorResponse('meter_id is a required parameter', $format);
        }

        if (!$meter = $this->meterStorage->findOneByMeterId($meterId)) {
            return $this->createErrorResponse('meter_id not found', $format);
        }

        $meterData = array_merge($meter, array_filter(array(
            'num_tokens'   => $request->get('num_tokens'),
            'should_warn'  => $request->get('should_warn'),
            'should_limit' => $request->get('should_limit'),
            'limit_threshold' => $request->get('limit_threshold'),
            'warn_threshold'  => $request->get('warn_threshold'),
            'throttle_ms'  => $request->get('throttle_ms'),
        ), 'self::filterNullValues'));

        if (!$meter = $this->meterStorage->saveMeterData($meterData)) {
            return $this->createErrorResponse('Unable to save meter', $format);
        }

        $content = $this->templating->render(
            sprintf('PerimeterRateLimitBundle:MeterApi:put.%s.twig', $format),
            array('meter' => $meter)
        );

        return $this->createResponseObject($content);
    }

    public function deleteAction(Request $request)
    {
        $format = $request->get('_format');

        if (!$meterId = $request->get('meter_id')) {
            return $this->createErrorResponse('meter_id is a required parameter', $format);
        }

        if (!$meter = $this->meterStorage->findOneByMeterId($meterId)) {
            return $this->createErrorResponse('meter_id not found', $format);
        }

        if (!$this->meterStorage->deleteMeter($meterId)) {
            return $this->createErrorResponse('Unable to delete meter', $format);
        }

        $content = $this->templating->render(
            sprintf('PerimeterRateLimitBundle:MeterApi:delete.%s.twig', $format),
            array('meter' => $meter)
        );

        return $this->createResponseObject($content);
    }

    protected function createErrorResponse($error, $format = 'json', $statusCode = 400)
    {
        $content = $this->templating->render(
            sprintf('PerimeterRateLimitBundle:MeterApi:error.%s.twig', $format),
            array('error' => $error)
        );

        return $this->createResponseObject($content, $statusCode);
    }

    protected function createResponseObject($content, $statusCode = 200, $headers = array())
    {
        return new $this->responseClass($content, $statusCode, $headers);
    }

    public static function filterNullValues($value)
    {
        return !is_null($value);
    }
}
