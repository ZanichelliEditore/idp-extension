<?php

namespace Zanichelli\IdpExtension\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogoutController extends Controller
{
    /**
     *  Logout from idp
     * @return void
     */
    public function logoutIdp(Request $request)
    {
        DB::table('sessions')
            ->where('token', $request->input('token'))
            ->where('user_id', $request->input('id'))
            ->delete();

        return response()->json([], 200);
    }
}
