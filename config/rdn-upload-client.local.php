<?php

return [
    'rdn_upload' => array(
        'adapter' => 'SyncFilesystem',
    ),
    'rdn_upload_adapters' => array(
        'configs' => array(
            'SyncFilesystem' => array(
                'host' => 'https://remote-host/api/v1/rdn-upload',
// HTTP authentication data
//                'host_access' => [
//                    'username' => '',
//                    'password' => '',
//                ],
                'access_token' => '<your-access-token>',
                'events' => ['get', 'update', 'delete'],
            ),
        ),
    ),
];
