<?php

namespace RdnUpload\Factory\Adapter;

use RdnUpload\Adapter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SyncFilesystem implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\Adapter\Filesystem
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');
        $configSync = $config['rdn_upload_adapters']['configs']['SyncFilesystem'];
        $config = $config['rdn_upload_adapters']['configs']['Filesystem'];

        if ($container->has('ViewHelperManager')) {
            $helpers = $container->get('ViewHelperManager');
            $config['public_path'] = call_user_func($helpers->get('BasePath'), $config['public_path']);
        }

        $access = NULL;
        if (isset($configSync['host_access']) && isset($configSync['host_access']['username']) && isset($configSync['host_access']['password'])) {
            $access = $configSync['host_access'];
        }

        return new Adapter\SyncFilesystem($config['upload_path'], $config['public_path'], $configSync['host'], $configSync['access_token'], $access, $configSync['events']);
    }

}
