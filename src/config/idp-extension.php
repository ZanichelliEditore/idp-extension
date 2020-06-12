
<?php

return [
    'IDP_URL' => env('IDP_URL', 'https://idp.zanichelli.it/loginForm'),
    'IDP_TOKEN_URL' => env('IDP_TOKEN_URL', 'https://idp.zanichelli.it/v1/user'),
    'IDP_LOGOUT_URL' => env('IDP_LOGOUT_URL', 'https://idp.zanichelli.it/v1/logout'),
    'IDP_LOGIN_URL' => env('IDP_LOGIN_URL', 'https://idp.zanichelli.it/v2/login')
]

?>