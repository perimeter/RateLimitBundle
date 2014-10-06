<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Storage;

use Perimeter\RateLimitBundle\Storage\DoctrineMeterStorage;

class DoctrineMeterStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMeter()
    {
        // create mock storage service that will always return meter for 'getMeter'
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(array('getOneOrNullResult', '_doExecute', 'getSQL', 'setParameter'))
            ->disableOriginalConstructor()
            ->getMock();
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->will($this->returnValue(array(
                'warn_threshold'  => true,
                'limit_threshold' => true,
                'should_warn'     => true,
                'should_limit'    => true,
                'num_tokens'      => true,
            )));
        $query->expects($this->once())
            ->method('setParameter')
            ->will($this->returnValue(null));
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            //->setMethods(array('createQuery'))
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
             ->method('createQuery')
             ->will($this->returnValue($query));

        $storage = new DoctrineMeterStorage($em);

        $company = 'Test Company '.rand();
        $this->assertNotNull($meter = $storage->getMeter('testuser:'.$company));
        $this->assertTrue($meter['should_limit']);
        $this->assertEquals('testuser:'.$company, $meter['meter_id']);
    }
}
