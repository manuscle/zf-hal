<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2017 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Hal\View;

use PHPUnit\Framework\TestCase;
use Zend\View\HelperPluginManager;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\View\ApiProblemRenderer;
use ZF\Hal\Collection;
use ZF\Hal\Entity;
use ZF\Hal\Plugin\Hal as HalPlugin;
use ZF\Hal\View\HalJsonModel;
use ZF\Hal\View\HalJsonRenderer;

/**
 * @subpackage UnitTest
 */
class HalJsonRendererTest extends TestCase
{
    /**
     * @var HalJsonRenderer
     */
    protected $renderer;

    public function setUp()
    {
        $this->renderer = new HalJsonRenderer(new ApiProblemRenderer());
    }

    public function nonHalJsonModels()
    {
        return [
            'view-model'      => [new ViewModel(['name' => 'foo'])],
            'json-view-model' => [new JsonModel(['name' => 'foo'])],
        ];
    }

    /**
     * @dataProvider nonHalJsonModels
     *
     * @param ViewModel $model
     */
    public function testRenderGivenNonHalJsonModelShouldReturnDataInJsonFormat($model)
    {
        $payload = $this->renderer->render($model);

        $this->assertEquals(
            $model->getVariables(),
            json_decode($payload, true)
        );
    }

    public function testRenderGivenHalJsonModelThatContainsHalEntityShouldReturnDataInJsonFormat()
    {
        $entity = [
            'id'   => 123,
            'name' => 'foo',
        ];
        $halEntity = new Entity($entity, 123);
        $model = new HalJsonModel(['payload' => $halEntity]);

        $helperPluginManager = $this->getHelperPluginManager();

        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderEntity')
            ->with($halEntity)
            ->will($this->returnValue($entity));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        $this->assertEquals($entity, json_decode($rendered, true));
    }

    public function testRenderGivenHalJsonModelThatContainsHalCollectionShouldReturnDataInJsonFormat()
    {
        $collection = [
            ['id' => 'foo', 'name' => 'foo'],
            ['id' => 'bar', 'name' => 'bar'],
            ['id' => 'baz', 'name' => 'baz'],
        ];
        $halCollection = new Collection($collection);
        $model = new HalJsonModel(['payload' => $halCollection]);

        $helperPluginManager = $this->getHelperPluginManager();

        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderCollection')
            ->with($halCollection)
            ->will($this->returnValue($collection));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        $this->assertEquals($collection, json_decode($rendered, true));
    }

    public function testRenderGivenHalJsonModelReturningApiProblemShouldReturnApiProblemInJsonFormat()
    {
        $halCollection = new Collection([]);
        $model = new HalJsonModel(['payload' => $halCollection]);

        $apiProblem = new ApiProblem(500, 'error');

        $helperPluginManager = $this->getHelperPluginManager();

        $halPlugin = $helperPluginManager->get('Hal');
        $halPlugin
            ->expects($this->once())
            ->method('renderCollection')
            ->with($halCollection)
            ->will($this->returnValue($apiProblem));

        $this->renderer->setHelperPluginManager($helperPluginManager);

        $rendered = $this->renderer->render($model);

        $apiProblemData = [
            'type'   => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title'  => 'Internal Server Error',
            'status' => 500,
            'detail' => 'error',
        ];
        $this->assertEquals($apiProblemData, json_decode($rendered, true));
    }

    private function getHelperPluginManager()
    {
        $helperPluginManager = $this->createMock(HelperPluginManager::class);
        $halPlugin = $this->createMock(HalPlugin::class);

        $helperPluginManager
            ->method('get')
            ->with('Hal')
            ->will($this->returnValue($halPlugin));

        return $helperPluginManager;
    }
}
