<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Closure;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Cache;

class IdpApiMiddleware
{
    private const CACHE_KEY = 'public_key';

    public function handle(Request $request, Closure $next, string $withV1User = 'with_v1_user',)
    {
        $tokenFromHeaders = $request->header()['token'][0];
        $token = ($request->input('token') ?? $request->cookies->get(config("idp.cookie.name"))) ?? $tokenFromHeaders;

        if ($token) {
            try {
                $client = new Client(['verify' => false]);
                if ($withV1User === 'with_v1_user') {
                    $res = $client->get(env('IDP_BASE_URL') . '/v1/user?token=' . $token);
                    $user = json_decode($res->getBody(), true);
                } else {
                    $jwk = Cache::get(self::CACHE_KEY);
                    if (!$jwk) {
                        $res = $client->get(env('IDP_BASE_URL') . '/.well-known/jwks.json');
                        $jwk = json_decode($res->getBody());
                        $date = date("d-m-Y H:i:s");
                        Cache::put(self::CACHE_KEY, $jwk);
                    }

                    $user = (array) JWT::decode($token, JWK::parseKey((array) $jwk->keys[0]));
                }

                $user['isVerified'] = $user['is_verified'];
                $user['isEmployee'] = $user['is_employee'];
                $user['createdAt'] = $user['created_at'];
                unset($user['is_verified'], $user['is_employee'], $user['created_at']);

                if ($withV1User !== 'with_v1_user') {
                    unset($user['iat'], $user['exp'], $user['nbf'], $user['sub'], $user['prv']);
                }

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
}
