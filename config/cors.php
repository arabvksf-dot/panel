<?php

return [
    












    



    'paths' => ['/api/client', '/api/application', '/api/client/*', '/api/application/*'],

    


    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'],

    


    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APP_CORS_ALLOWED_ORIGINS', ''))
    ))),

    


    'allowed_origins_patterns' => [],

    


    'allowed_headers' => ['*'],

    


    'exposed_headers' => [],

    


    'max_age' => 0,

    


    'supports_credentials' => true,
];
