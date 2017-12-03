<?php

namespace RdnUpload\Factory\Controller\Plugin;

//use RdnUpload\ContainerInterface;
use RdnUpload\Controller\Plugin;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class Uploads implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\Controller\Plugin\Uploads
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ContainerInterface $uploads */
        $uploads = $container->get(\RdnUpload\Container::class);
        return new Plugin\Uploads($uploads);
    }

}
