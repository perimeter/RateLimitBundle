<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Tests\Controller;

use Perimeter\RateLimitBundle\Controller\MeterApiController;
use Symfony\Component\HttpFoundation\Request;

class MeterApiControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMeterId()
    {
        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(
                array('meter_id' => 'foo')
            ));

        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->once())
            ->method('render')
            ->will($this->returnCallback(function ($template, $variables) { return json_encode($variables['meters']); }));

        $controller = new MeterApiController($storage, $templating);

        $request = new Request();
        $request->query->set('meter_id', 1);

        $response = $controller->getAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('[{"meter_id":"foo"}]', $response->getContent());
    }

    public function testGetSearch()
    {
        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->once())
            ->method('findBySearch')
            ->will($this->returnValue(array(
                array('meter_id' => 'foo'),
            )));

        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->once())
            ->method('render')
            ->will($this->returnCallback(function ($template, $variables) { return json_encode($variables['meters']); }));

        $controller = new MeterApiController($storage, $templating);

        $request = new Request();
        $request->query->set('search', 'foo');

        $response = $controller->getAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('[{"meter_id":"foo"}]', $response->getContent());
    }

    public function testGetAll()
    {
        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(array(
                array('meter_id' => 'foo'),
            )));

        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->once())
            ->method('render')
            ->will($this->returnCallback(function ($template, $variables) { return json_encode($variables['meters']); }));

        $controller = new MeterApiController($storage, $templating);

        $request = new Request();

        $response = $controller->getAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('[{"meter_id":"foo"}]', $response->getContent());
    }

    public function testPost()
    {
        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->exactly(2))
            ->method('addMeter')
            ->will($this->returnCallback(function ($meterData) { return $meterData; }));

        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnCallback(function ($template, $variables) {
                return strpos($template, 'error') ? json_encode(array('error' => $variables['error'])) : json_encode($variables['meter']);
            }));

        $controller = new MeterApiController($storage, $templating);

        // meter_id must be set
        $request = new Request();
        $request->setMethod('POST');
        $response = $controller->postAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"meter_id is a required parameter"}', $response->getContent());

        // limit_threshold must be set
        $request->request->set('meter_id', 1);
        $response = $controller->postAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"limit_threshold is a required parameter"}', $response->getContent());

        // successful add
        $request->request->set('limit_threshold', 1);
        $response = $controller->postAction($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('{"meter_id":1,"limit_threshold":1}', $response->getContent());

        // bogus parameter does not get added
        $request->request->set('foo-goop', 1);
        $response = $controller->postAction($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('{"meter_id":1,"limit_threshold":1}', $response->getContent());

        // cannot add existing meter_id
        $storage->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(array('meter_id' => 1)));

        $response = $controller->postAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"a meter already exists for this meter_id"}', $response->getContent());
    }

    public function testPut()
    {
        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(false));

        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnCallback(function ($template, $variables) {
                return strpos($template, 'error') ? json_encode(array('error' => $variables['error'])) : json_encode($variables['meter']);
            }));

        $controller = new MeterApiController($storage, $templating);

        // meter_id must be set
        $request = new Request();
        $request->setMethod('PUT');
        $response = $controller->putAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"meter_id is a required parameter"}', $response->getContent());

        $request->request->set('meter_id', 1);
        $response = $controller->putAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"meter_id not found"}', $response->getContent());

        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(array('meter_id' => 1)));
        $storage->expects($this->once())
            ->method('saveMeterData')
            ->will($this->returnValue(array('meter_id' => 1)));

        $controller = new MeterApiController($storage, $templating);
        $response = $controller->putAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"meter_id":1}', $response->getContent());
    }

    public function testDelete()
    {
        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(false));

        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $templating->expects($this->any())
            ->method('render')
            ->will($this->returnCallback(function ($template, $variables) {
                return strpos($template, 'error') ? json_encode(array('error' => $variables['error'])) : '';
            }));

        $controller = new MeterApiController($storage, $templating);

        // meter_id must be set
        $request = new Request();
        $request->setMethod('DELETE');
        $response = $controller->deleteAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"meter_id is a required parameter"}', $response->getContent());

        $request->request->set('meter_id', 1);
        $response = $controller->putAction($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('{"error":"meter_id not found"}', $response->getContent());

        $storage = $this->getMock('Perimeter\RateLimiter\Storage\MeterStorageAdminInterface');
        $storage->expects($this->once())
            ->method('findOneByMeterId')
            ->will($this->returnValue(array('meter_id' => 1)));
        $storage->expects($this->once())
            ->method('deleteMeter')
            ->will($this->returnValue(true));

        $controller = new MeterApiController($storage, $templating);
        $response = $controller->deleteAction($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
    }
}
