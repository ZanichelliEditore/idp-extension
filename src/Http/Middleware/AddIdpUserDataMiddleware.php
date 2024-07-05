<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Closure;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Zanichelli\IdpExtension\Models\ZTrait\ZUserBuilder;

class AddIdpUserDataMiddleware
{
    use ZUserBuilder;

    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookies->get(config("idp.cookie.name"));
        if ($token) {
            try {
                $client = new Client(['verify' => false]);
                $res = $client->get(env('IDP_URL') . '/v1/user?token=' . $token);
                $request->merge(['user' => json_decode($res->getBody(), true)]);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }
        }

        return $next($request);
    }
}
