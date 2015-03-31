<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Resolver;

use Perimeter\RateLimiter\Resolver\MeterResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class SecurityContextMeterResolver implements MeterResolverInterface
{
    protected $securityContext;
    protected $config;

    public function __construct(SecurityContextInterface $securityContext, $config = array())
    {
        $this->securityContext = $securityContext;
        $this->config = array_merge(array(
            'case_insensitive' => true,
        ), $config);
    }

    public function getMeterIdentifier()
    {
        if (!$token = $this->securityContext->getToken()) {
            return;
        }

        return $this->config['case_insensitive'] ? strtolower($token->getUsername()) : $token->getUsername();
    }
}
