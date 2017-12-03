<?php

namespace RdnUpload\Factory;

use RdnUpload;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class Container implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\Container
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');

        $adapters = $container->get(RdnUpload\Adapter\AdapterManager::class);
        $adapter = $adapters->get($config['rdn_upload']['adapter']);

        return new RdnUpload\Container($adapter, $config);
    }

}
