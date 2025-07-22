<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],  // 仅允许API路由跨域

    'allowed_methods' => ['*'],  // 允许所有HTTP方法

    // 'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:9000')],  // 允许的前端地址
    'allowed_origins' => ['*'],  // 允许的前端地址

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],  // 允许所有请求头（可以根据需求调整）

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,  // 允许携带cookie和token

];

