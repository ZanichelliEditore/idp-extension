<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Carbon\Carbon;
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
                    $cachedData = Cache::store('file')->get(self::CACHE_KEY);
                    $now = Carbon::now();

                    if (!$cachedData || ($cachedData && $cachedData['date']->diffInDays(Carbon::tomorrow()) > 1)) {
                        $res = $client->get(env('IDP_BASE_URL') . '/.well-known/jwks.json');
                        $jwk = json_decode($res->getBody());
                        Cache::store('file')->put(self::CACHE_KEY, ['date' => $now, 'jwk' => $jwk]);
                    } else {
                        $jwk = $cachedData['jwk'];
                    }

                    $user = (array) JWT::decode($token, JWK::parseKey((array) $jwk->keys[0]));
                }

                if ($withV1User !== 'with_v1_user') {
                    $user['name'] = $user['given_name'];
                    $user['surname'] = $user['family_name'];
                    $user['username'] = $user['preferred_username'];
                    $user['roles'] = $this->getFormattedRoles($user['roles']);
                    $user['attributes'] = $this->getFormattedAttributes($user['attributes']);
                    $user['myz'] = (array) $user['myz'];

                    unset($user['iat'], $user['exp'], $user['nbf'], $user['sub'], $user['prv'], $user['given_name'], $user['family_name'], $user['preferred_username']);
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

    protected function getFormattedRoles(array $roles): array
    {
        return array_map(function ($role) {
            return [
                "id" => $role->id,
                "roleId" => $role->role_id,
                "roleName" => $role->roleName,
                "departmentId" => $role->department_id,
                "departmentName" => null,
                "branchCode" => null,
            ];
        }, $roles);
    }

    protected function getFormattedAttributes(object $attributes): array
    {
        $parsedAttributes = [];

        foreach ($attributes as $key => $value) {
            $parsedAttributes[] = [
                "key" => $key,
                "value" => $value
            ];
        }
        return $parsedAttributes;
    }
}
