<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!file_exists($autoload_file = __DIR__.'/../vendor/autoload.php')) {
    if (empty($_SERVER['PERIMETER_VENDOR_AUTOLOAD'])) {
        throw new Exception('You must run composer.phar install, or set the PERIMETER_VENDOR_AUTOLOAD environment variable in your phpunit.xml to run the bundle tests');
    }

    $autoload_file = $_SERVER['PERIMETER_VENDOR_AUTOLOAD'];
}

require_once $autoload_file;

// Do Doctrine's job
Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__.'/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

if (!empty($_SERVER['UPDATE_DOCTRINE_DB'])) {
    // build the database
    if (!$container = Perimeter\RateLimitBundle\Tests\ContainerLoader::buildTestContainer()) {
        throw new Exception('Cannot update Doctrine DB: '.ContainerLoader::$errorMessage);
    }
    $em = $container->get('doctrine.orm.entity_manager');
    $metadatas = $em->getMetadataFactory()->getAllMetadata();

    if ( ! empty($metadatas)) {
        // Create SchemaTool
        $schemaTool = new Doctrine\ORM\Tools\SchemaTool($em);

        if ($sqls = $schemaTool->getUpdateSchemaSql($metadatas)) {
            $schemaTool->updateSchema($metadatas);
        }
    }
}
