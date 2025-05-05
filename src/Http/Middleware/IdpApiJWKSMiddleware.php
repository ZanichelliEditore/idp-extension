<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Cache;

class IdpApiJWKSMiddleware extends IdpApiMiddleware
{
    private const CACHE_KEY = 'idp_jwks_public_key';

    protected function getUser(string $token): array
    {
        if ($jwk = Cache::store('file')->get(self::CACHE_KEY)) {
            $res = $this->client->get('/.well-known/jwks.json');
            $jwk = json_decode($res->getBody());
            Cache::store('file')->put(self::CACHE_KEY, $jwk);
        }

        $user = (array) JWT::decode($token, JWK::parseKey((array) $jwk->keys[0]));
        $user['name'] = $user['given_name'];
        $user['surname'] = $user['family_name'];
        $user['username'] = $user['preferred_username'];
        $user['roles'] = $this->getFormattedRoles($user['roles']);
        $user['attributes'] = $user['attributes'] ? $this->getFormattedAttributes($user['attributes']) : [];
        $user['myz'] = (array) $user['myz'];
        unset($user['iat'], $user['exp'], $user['nbf'], $user['sub'], $user['prv'], $user['given_name'], $user['family_name'], $user['preferred_username']);
        return $user;
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
