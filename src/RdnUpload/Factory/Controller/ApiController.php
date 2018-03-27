<?php

namespace RdnUpload\Factory\Controller;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ApiController implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\Controller\ApiController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        return new \RdnUpload\Controller\ApiController($config);
    }

}
