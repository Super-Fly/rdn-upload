<?php

namespace RdnUpload\Factory\Adapter;

use RdnUpload\Adapter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Filesystem implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\Adapter\Filesystem
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        $config = $config['rdn_upload_adapters']['configs']['Filesystem'];

        if ($container->has('ViewHelperManager')) {
            $helpers = $container->get('ViewHelperManager');
            $config['public_path'] = call_user_func($helpers->get('BasePath'), $config['public_path']);
        }

        return new Adapter\Filesystem($config['upload_path'], $config['public_path']);
    }

}
