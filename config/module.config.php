<?php

return array(
    'controllers' => array(
        'factories' => array(
            \RdnUpload\Controller\ApiController::class => \RdnUpload\Factory\Controller\ApiController::class,
        ),
    ),
    'controller_plugins' => array(
        'aliases' => array(
            'uploads' => 'RdnUpload:Uploads',
        ),
        'factories' => array(
            'RdnUpload:Uploads' => \RdnUpload\Factory\Controller\Plugin\Uploads::class,
            'Filesystem' => RdnUpload\Factory\Adapter\Filesystem::class,
            'SyncFilesystem' => RdnUpload\Factory\Adapter\SyncFilesystem::class,
        ),
        'configs' => array(
            'Filesystem' => array(
                'upload_path' => 'data/uploads',
                'public_path' => '/files',
            ),
        ),
    ),
    'rdn_upload' => array(
        'adapter' => 'Filesystem',
        'temp_dir' => null,
    ),
    'rdn_upload_adapters' => array(
        'factories' => array(
            'Filesystem' => RdnUpload\Factory\Adapter\Filesystem::class,
            'SyncFilesystem' => RdnUpload\Factory\Adapter\SyncFilesystem::class,
        ),
        'configs' => array(
            'Filesystem' => array(
                'upload_path' => 'data/uploads',
                'public_path' => '/files',
            ),
            'SyncFilesystem' => array(
                'host' => NULL,
                'access_token' => NULL,
                'events' => [], // 'get', 'update', 'delete'
            ),
        ),
    ),
    'rdn_upload_remote' => array(
        'access_token' => NULL,
    ),
    'service_manager' => array(
        'factories' => array(
            'RdnUpload\Adapter\AdapterManager' => RdnUpload\Factory\Adapter\AdapterManager::class,
            'RdnUpload\Container' => RdnUpload\Factory\Container::class,
        ),
    ),
    'view_helpers' => array(
        'aliases' => array(
            'uploads' => 'RdnUpload:Uploads',
        ),
        'factories' => array(
            'RdnUpload:Uploads' => RdnUpload\Factory\View\Helper\Uploads::class,
        ),
    ),
);
