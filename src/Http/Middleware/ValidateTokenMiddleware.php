<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Zanichelli\IdpExtension\Models\ZTrait\ZUserBuilder;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class ValidateTokenMiddleware
{
    use ZUserBuilder;

    public function handle(Request $request, Closure $next)
    {
        if ($token = $request->input('token')) {
            $client = new Client(['verify' => false]);

            try {
                $response = $client->get(env('IDP_BASE_URL') . '/.well-known/jwks.json');

                if ($response->getStatusCode() == 200) {
                    $jwk = json_decode($response->getBody());
                    $user = JWT::decode($token, JWK::parseKey((array) $jwk->keys[0]));

                    $user->isVerified = $user->is_verified;
                    $user->isEmployee = $user->is_employee;
                    $user->createdAt = $user->created_at;
                    unset($user->is_verified, $user->is_employee, $user->created_at);

                    $request->merge(['user' => (array) $user]);
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([], 401);
                }

                return redirect(env('IDP_BASE_URL') . '/loginForm?redirect=' . $request->url());
            }
        }
        return $next($request);
    }
}
