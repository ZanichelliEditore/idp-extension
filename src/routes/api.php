<?php

use Illuminate\Http\Request;

/**
 * Api routes
 */
Route::post('/api/logout-idp', 'Zanichelli\IdpExtension\Http\Controllers\LogoutController@logoutIdp')->name('logoutIdp');
