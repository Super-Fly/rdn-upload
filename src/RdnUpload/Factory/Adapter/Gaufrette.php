<?php

namespace RdnUpload\Factory\Adapter;

use RdnUpload\Adapter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Gaufrette implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\Adapter\Gaufrette
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        $config = $config['rdn_upload_adapters']['configs']['Gaufrette'];

        if (!isset($config['filesystem'])) {
            throw new \InvalidArgumentException("You must set the 'rdn_upload_adapters.configs.Gaufrette.filesystem' configuration option to a valid Gaufrette filesystem service name");
        }

        $filesystem = $container->get($config['filesystem']);
        return new Adapter\Gaufrette($filesystem, $config['public_path']);
    }

}
