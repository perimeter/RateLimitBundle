<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Command;

use Symfony\Component\DependencyInjection\Container;
use Perimeter\RateLimitBundle\Command\MeterCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Kernel;

class MeterCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMeter()
    {
        if (version_compare(Kernel::VERSION, '2.4.0', '<')) {
            return $this->markTestSkipped('this test is not available for symfony 2.3');
        }

        $meterId = 'meter-id-'.rand();

        $admin = $this->getMock('Perimeter\RateLimitBundle\Storage\MeterStorageAdminInterface');
        $admin->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(null));

        $container = new Container();
        $container->set('perimeter.rate_limit.storage.admin', $admin);
        $command = new MeterCommand();
        $command->setContainer($container);

        $input = new ArgvInput(array('command', $meterId));
        $output = new BufferedOutput();

        $statusCode = $command->run($input, $output);

        $this->assertEquals(1, $statusCode);
        $this->assertTrue(false !== strpos($output->fetch(), sprintf('meter %s does not exist', $meterId)));

        $admin = $this->getMock('Perimeter\RateLimitBundle\Storage\MeterStorageAdminInterface');
        $admin->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(array('meter_id' => $meterId)));

        $container->set('perimeter.rate_limit.storage.admin', $admin);

        $statusCode = $command->run($input, $output);

        $this->assertEquals(0, $statusCode);
        $this->assertTrue(false !== strpos($output->fetch(), sprintf('meter_id: %s', $meterId)));
    }

    public function testDeleteMeter()
    {
        if (version_compare(Kernel::VERSION, '2.4.0', '<')) {
            return $this->markTestSkipped('this test is not available for symfony 2.3');
        }

        $meterId = 'meter-id-'.rand();

        $admin = $this->getMock('Perimeter\RateLimitBundle\Storage\MeterStorageAdminInterface');
        $admin->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(null));

        $container = new Container();
        $container->set('perimeter.rate_limit.storage.admin', $admin);
        $command = new MeterCommand();
        $command->setContainer($container);

        $input = new ArgvInput(array('command', $meterId, '--delete'));
        $output = new BufferedOutput();

        $statusCode = $command->run($input, $output);

        $this->assertEquals(1, $statusCode);
        $this->assertTrue(false !== strpos($output->fetch(), sprintf('cannot delete: meter %s does not exist', $meterId)));

        $admin = $this->getMock('Perimeter\RateLimitBundle\Storage\MeterStorageAdminInterface');
        $admin->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(array('meter_id' => $meterId)));
        $admin->expects($this->once())
            ->method('deleteMeter')
            ->will($this->returnValue(true));

        $container->set('perimeter.rate_limit.storage.admin', $admin);

        $statusCode = $command->run($input, $output);

        $this->assertEquals(0, $statusCode);
        $this->assertTrue(false !== strpos($output->fetch(), sprintf('meter %s deleted successfully', $meterId)));
    }

    public function testCreateMeter()
    {
        if (version_compare(Kernel::VERSION, '2.4.0', '<')) {
            return $this->markTestSkipped('this test is not available for symfony 2.3');
        }

        $meterId = 'meter-id-'.rand();

        $admin = $this->getMock('Perimeter\RateLimitBundle\Storage\MeterStorageAdminInterface');
        $admin->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(null));
        $admin->expects($this->once())
            ->method('addMeter')
            ->will($this->returnValue(array(
                'meter_id' => $meterId,
                'warn_threshold' => 10,
                'limit_threshold' => 15,
            )));

        $container = new Container();
        $container->set('perimeter.rate_limit.storage.admin', $admin);
        $command = new MeterCommand();
        $command->setContainer($container);

        $input = new ArgvInput(array('command', $meterId, 10, 15));
        $output = new BufferedOutput();

        $statusCode = $command->run($input, $output);

        $this->assertEquals(0, $statusCode);
        $output = $output->fetch();
        $this->assertTrue(false !== strpos($output, sprintf('meter %s created successfully', $meterId)));
        $this->assertTrue(false !== strpos($output, sprintf('meter_id: %s', $meterId)));
        $this->assertTrue(false !== strpos($output, 'warn_threshold: 10'));
        $this->assertTrue(false !== strpos($output, 'limit_threshold: 15'));
    }

    public function testUpdateMeter()
    {
        if (version_compare(Kernel::VERSION, '2.4.0', '<')) {
            return $this->markTestSkipped('this test is not available for symfony 2.3');
        }

        $meterId = 'meter-id-'.rand();

        $admin = $this->getMock('Perimeter\RateLimitBundle\Storage\MeterStorageAdminInterface');
        $admin->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(array(
                'meter_id' => $meterId,
                'warn_threshold' => 10,
                'limit_threshold' => 15,
            )));
        $admin->expects($this->once())
            ->method('saveMeterData')
            ->will($this->returnValue(array(
                'meter_id' => $meterId,
                'warn_threshold' => 1,
                'limit_threshold' => 2,
            )));

        $container = new Container();
        $container->set('perimeter.rate_limit.storage.admin', $admin);
        $command = new MeterCommand();
        $command->setContainer($container);

        $input = new ArgvInput(array('command', $meterId, 10, 15));
        $output = new BufferedOutput();

        $statusCode = $command->run($input, $output);

        $this->assertEquals(0, $statusCode);
        $output = $output->fetch();
        $this->assertTrue(false !== strpos($output, sprintf('meter %s updated successfully', $meterId)));
        $this->assertTrue(false !== strpos($output, sprintf('meter_id: %s', $meterId)));
        $this->assertTrue(false !== strpos($output, 'warn_threshold: 1'));
        $this->assertTrue(false !== strpos($output, 'limit_threshold: 2'));
    }
}
