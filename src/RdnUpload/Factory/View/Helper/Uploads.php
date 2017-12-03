<?php

namespace RdnUpload\Factory\View\Helper;

//use RdnUpload\ContainerInterface;
use RdnUpload\View\Helper;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class Uploads implements FactoryInterface
{

    /**
     * @inheritdoc
     * @return \RdnUpload\View\Helper\Uploads
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ContainerInterface $uploads */
        $uploads = $container->get('RdnUpload\Container');
        return new Helper\Uploads($uploads);
    }

}
