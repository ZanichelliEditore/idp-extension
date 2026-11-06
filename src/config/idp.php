<?php

return [
    'connection' => env("DB_CONNECTION", 'mysql'),
    'cookie' => ["name" => env("IDP_COOKIE_NAME")],
    'base_url' => env("IDP_BASE_URL"),
    'login_url' => env("IDP_LOGIN_URL"),
];
