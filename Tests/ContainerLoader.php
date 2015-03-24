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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

class ContainerLoader
{
    public static $errorMessage;

    public static function buildTestContainer()
    {
        if (!isset($_SERVER['CONTAINER_CONFIG'])) {
            self::$errorMessage = 'Must set CONTAINER_CONFIG in phpunit.xml or environment variable';

            return;
        }

        $container = new ContainerBuilder();
        $locator   = new FileLocator(__DIR__ . '/..');
        $loader    = new XmlFileLoader($container, $locator);

        $loader->load($_SERVER['CONTAINER_CONFIG']);

        //  give the container some context
        $container->setParameter('bundle_root_dir', __DIR__.'/..');

        return $container;
    }
}
