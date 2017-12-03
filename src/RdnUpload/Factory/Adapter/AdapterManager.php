<?php

namespace RdnUpload\Factory\Adapter;

use RdnUpload\Adapter;
use Zend\ServiceManager\Config;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AdapterManager implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\Adapter\AdapterManager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');

        $adapters = new Adapter\AdapterManager($container, $config['rdn_upload_adapters']);

        return $adapters;
    }

}
