<?php

return [
    'rdn_upload_remote' => array(
        'access_token' => '<server-access-token>',
    ),
    'router' => array(
        'routes' => array(
            'api-rdn-upload' => array(
                'type' => \Zend\Router\Http\Segment::class,
                'options' => array(
                    'route' => '/api/v1/rdn-upload',
                    'defaults' => array(
                        'controller' => \RdnUpload\Controller\ApiController::class,
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => TRUE,
                'child_routes' => array(
                    'get' => array(
                        'type' => \Zend\Router\Http\Segment::class,
                        'options' => array(
                            'route' => '/get',
                            'defaults' => array(
                                'action' => 'get'
                            ),
                        ),
                    ),
                    'upload' => array(
                        'type' => \Zend\Router\Http\Segment::class,
                        'options' => array(
                            'route' => '/upload',
                            'defaults' => array(
                                'action' => 'upload'
                            ),
                        ),
                    ),
                    'delete' => array(
                        'type' => \Zend\Router\Http\Segment::class,
                        'options' => array(
                            'route' => '/delete',
                            'defaults' => array(
                                'action' => 'delete'
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
];