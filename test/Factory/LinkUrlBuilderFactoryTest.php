<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2017 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Hal\Factory;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Helper;
use ZF\Hal\Factory\LinkUrlBuilderFactory;
use ZF\Hal\Link\LinkUrlBuilder;

class LinkUrlBuilderFactoryTest extends TestCase
{
    /**
     * @var Helper\ServerUrl
     */
    private $serverUrlHelper;

    /**
     * @var Helper\Url
     */
    private $urlHelper;

    public function testInstantiatesLinkUrlBuilder()
    {
        $serviceManager = $this->getServiceManager();

        $factory = new LinkUrlBuilderFactory();
        $builder = $factory($serviceManager);

        $this->assertInstanceOf(LinkUrlBuilder::class, $builder);
    }

    public function testOptionUseProxyIfPresentInConfig()
    {
        $options = [
            'options' => [
                'use_proxy' => true,
            ],
        ];
        $serviceManager = $this->getServiceManager($options);

        $this->serverUrlHelper
            ->setUseProxy($options['options']['use_proxy'])
            ->shouldBeCalled();

        $factory = new LinkUrlBuilderFactory();
        $factory($serviceManager);
    }

    private function getServiceManager($config = [])
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('ZF\Hal\HalConfig', $config);

        $viewHelperManager = new ServiceManager();
        $serviceManager->setService('ViewHelperManager', $viewHelperManager);

        $this->serverUrlHelper = $serverUrlHelper = $this->prophesize(Helper\ServerUrl::class);
        $viewHelperManager->setService('ServerUrl', $serverUrlHelper->reveal());

        $this->urlHelper = $urlHelper = $this->prophesize(Helper\Url::class);
        $viewHelperManager->setService('Url', $urlHelper->reveal());

        return $serviceManager;
    }
}
