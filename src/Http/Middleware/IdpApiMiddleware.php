<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Closure;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdpApiMiddleware
{
    protected $client;

    public function handle(Request $request, Closure $next)
    {
        $token = $request->input('token') ?? $request->cookies->get(config("idp.cookie.name")) ?? $request->headers->get('token');
        if ($token) {
            try {
                $this->client = new Client(['base_uri' => env('IDP_BASE_URL'), 'verify' => false]);

                $user = $this->getUser($token);

                $request->merge(['user' => $user]);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                return response()->json([], 401);
            }
        } else {
            return response()->json([], 401);
        }

        return $next($request);
    }

    protected function getUser($token)
    {
        $res = $this->client->get('/v1/user?token=' . $token);
        $user = json_decode($res->getBody(), true);
        return $user;
    }
}
