<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Listener;

use Perimeter\RateLimiter\Throttler\ThrottlerInterface;
use Perimeter\RateLimiter\Resolver\MeterResolverInterface;
use Perimeter\RateLimiter\Resolver\MultipleMeterResolverInterface;
use Perimeter\RateLimiter\Exception\RateLimitException;
use Perimeter\RateLimiter\Storage\MeterStorageInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package    pulsar
 * @subpackage filter
 * @author     Courtney Ferguson <cofergus@adobe.com>
 * @version    SVN: $Id: sfCacheFilter.class.php 28625 2010-03-19 19:00:53Z Kris.Wallsmith $
 */
class RateLimitListener
{
    private $shouldEmitWarningHeader;

    protected $throttler;
    protected $meterResolver;
    protected $meterStorage;
    protected $overLimitController;
    protected $debug;

    public function __construct(ThrottlerInterface $throttler, $meterResolver, MeterStorageInterface $meterStorage, $overLimitController, $debug = false)
    {
        if (!$meterResolver instanceof MeterResolverInterface && !$meterResolver instanceof MultipleMeterResolverInterface) {
            throw new \InvalidArgumentException('$meterResolver must implement either MeterResolverInterface or MultipleMeterResolverInterface');
        }

        $this->throttler = $throttler;
        $this->meterResolver = $meterResolver;
        $this->meterStorage = $meterStorage;
        $this->overLimitController = $overLimitController;
        $this->debug = $debug;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        try {
            if ($this->meterResolver instanceof MultipleMeterResolverInterface) {
                foreach ($this->meterResolver->getMeterIdentifiers() as $identifier) {
                    if ($meter = $this->meterStorage->getMeter($identifier['meter_id'], $identifier['type'])) {
                        $this->consumeMeter($meter);
                    }
                }
            } elseif ($meter = $this->meterStorage->getMeter($this->meterResolver->getMeterIdentifier())) {
                $this->consumeMeter($meter);
            }
        } catch (RateLimitException $e) {
            // one of the rate limit meters has been exceeded
            $response = $this->overLimitController->overLimitAction($event->getRequest(), $meter);
            $event->setResponse($response);
        } catch (\Exception $e) {
            // rate limiting errors should not bring down the service
            if ($this->debug) {
                throw $e;
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($this->shouldEmitWarningHeader) {
            $event->getResponse()->headers->set('X-RateLimit', 'Warning');
        }
    }

    protected function consumeMeter($meter)
    {
        $meter = array_merge(array(
            'should_warn'  => true,
            'should_limit' => true,
            'throttle_ms'  => 0,
            'num_tokens'   => 1,
        ), $meter);

        $this->throttler->consume($meter['meter_id'], $meter['warn_threshold'], $meter['limit_threshold'], $meter['num_tokens'], $meter['throttle_ms']);
        if ($this->throttler->isLimitWarning() && $meter['should_warn']) {
            $this->shouldEmitWarningHeader = true;
        } elseif ($this->throttler->isLimitExceeded() && $meter['should_limit']) {
            throw new RateLimitException($meter['meter_id']);
        }
    }
}
