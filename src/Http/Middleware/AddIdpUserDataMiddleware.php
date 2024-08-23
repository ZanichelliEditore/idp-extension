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

    public function handle(Request $request, Closure $next, string $idpUrlEnvKey = 'IDP_URL')
    {
        $token = $request->cookies->get(config("idp.cookie.name"));
        if ($token) {
            try {
                $client = new Client(['verify' => false]);
                $res = $client->get(env($idpUrlEnvKey) . '/v1/user?token=' . $token);
                $user = json_decode($res->getBody(), true);

                $user['isVerified'] = $user['is_verified'];
                $user['isEmployee'] = $user['is_employee'];
                $user['createdAt'] = $user['created_at'];
                unset($user['is_verified'], $user['is_employee'], $user['created_at']);

                $request->merge(['user' => $user]);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                return response()->json(['message' => $e->getMessage()], $e->getCode());
            }
        }

        return $next($request);
    }
}
