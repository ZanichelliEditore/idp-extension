<?php

use Illuminate\Http\Request;

/**
 * Api routes
 */
Route::post('/api/logout-idp', 'LogoutController@logoutIdp')->name('logoutIdp');
