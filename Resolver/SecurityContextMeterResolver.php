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

use Symfony\Component\Security\Core\SecurityContextInterface;

class SecurityContextMeterResolver implements MeterResolverInterface
{
    protected $securityContext;

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function getMeterIdentifier()
    {
        if (!$token = $this->securityContext->getToken()) {
            return;
        }

        return $token->getUsername();
    }
}
