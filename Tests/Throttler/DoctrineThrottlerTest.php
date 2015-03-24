<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Entity;

use Perimeter\RateLimitBundle\Tests\ContainerLoader;
use Perimeter\RateLimitBundle\Entity\RateLimitBucket;
use Perimeter\RateLimitBundle\Throttler\DoctrineThrottler;
use Doctrine\Common\Annotations\AnnotationRegistry;

class DoctrineThrottlerTest extends \PHPUnit_Framework_TestCase
{
    public function testThrottle()
    {
        AnnotationRegistry::registerFile(__DIR__.'/../../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

        if (!$container = ContainerLoader::buildTestContainer()) {
            return $this->markTestSkipped(ContainerLoader::$errorMessage);
        }

        $em = $container->get('doctrine.orm.entity_manager');

        $config = array(
            'bucket_size' => 300, // five minute buckets
            'num_buckets' => 2,   // two of them
        );

        $throttler = new DoctrineThrottler($em, $config);

        $time = time();
        $timeBlock  = $time - ($time % $config['bucket_size']);

        $meterId = 'meter-id-'.mt_rand();

        $throttler->consume($meterId, 2, 2);

        $stored = $em->getRepository('Perimeter\RateLimitBundle\Entity\RateLimitBucket')
          ->findOneBy(array('meter_id' => $meterId));

        $this->assertNotNull($stored);
        $this->assertEquals($meterId, $stored->meter_id);
        $this->assertEquals($timeBlock, $stored->time_block);
        $this->assertEquals(1, $stored->tokens);
        $this->assertFalse($throttler->isLimitWarning());
        $this->assertFalse($throttler->isLimitExceeded());

        $throttler->consume($meterId, 3, 3);

        $stored = $em->getRepository('Perimeter\RateLimitBundle\Entity\RateLimitBucket')
          ->findOneBy(array('meter_id' => $meterId));

        $this->assertNotNull($stored);
        $this->assertEquals($meterId, $stored->meter_id);
        $this->assertEquals($timeBlock, $stored->time_block);
        $this->assertEquals(2, $stored->tokens);
        $this->assertFalse($throttler->isLimitWarning());
        $this->assertFalse($throttler->isLimitExceeded());

        // set a ten-minute-ago bucket, and ensure the average is not used
        $bucket = new RateLimitBucket;
        $bucket->meter_id = $meterId;
        $bucket->time_block = $timeBlock - ($config['bucket_size'] * 2);
        $bucket->tokens = 5; /* (5 + 3) / 3 == 4, so if it averaged, thresholds would be exceeded */
        $em->persist($bucket);
        $em->flush();

        $throttler->consume($meterId, 4, 4);

        $this->assertFalse($throttler->isLimitWarning());
        $this->assertFalse($throttler->isLimitExceeded());

        // set a five-minute-ago bucket, and ensure the average is used
        $bucket = new RateLimitBucket;
        $bucket->meter_id = $meterId;
        $bucket->time_block = $timeBlock - $config['bucket_size'];
        $bucket->tokens = 6; /* (6 + 4) / 2 == 5, so thresholds should be exceeded */

        $em->persist($bucket);
        $em->flush();

        $throttler->consume($meterId, 5, 5);

        $this->assertTrue($throttler->isLimitWarning());
        $this->assertTrue($throttler->isLimitExceeded());
    }
}
