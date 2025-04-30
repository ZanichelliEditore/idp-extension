<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Zanichelli\IdpExtension\Models\ZTrait\ZUserBuilder;
use Zanichelli\IdpExtension\Models\ZUser;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class ValidateTokenMiddleware
{
    use ZUserBuilder;

    public function handle(Request $request, Closure $next, string $withPermissions = 'with_permissions', string $withTokenUrl = "without_token_url")
    {
        if ($token = $request->input('token')) {
            $client = new Client(['verify' => false]);

            try {
                $response = $client->get(env('IDP_BASE_URL') . '/.well-known/jwks.json');

                if ($response->getStatusCode() == 200) {
                    $jwk = json_decode($response->getBody());
                    $userJson = JWT::decode($token, JWK::parseKey((array) $jwk->keys[0]));

                    $roles = $this->createRoleArray($this->parseRoles($userJson->roles));

                    $attributes = $this->createAttributeArray($this->parseAttributes($userJson->attributes));

                    $permissions = [];
                    if ($withPermissions == 'with_permissions') {
                        $permissions = $this->retrievePermissions($userJson->id, $roles);
                    }

                    $user = ZUser::create(
                        $userJson->id,
                        $userJson->email,
                        $userJson->email,
                        $token,
                        $userJson->is_verified,
                        $userJson->given_name,
                        $userJson->family_name,
                        $userJson->is_employee,
                        $userJson->created_at,
                        $roles,
                        $permissions,
                        $attributes
                    );

                    $this->addExtraParametersToUser($user);

                    Auth::setUser($user);
                    if ($withTokenUrl == 'without_token_url') {
                        if ($request->query('token')) {
                            $request->query->remove('token');
                            return redirect(trim($request->url() . "?" . http_build_query($request->query->all()), "?"));
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([], 401);
                }

                return redirect(env('IDP_BASE_URL') . '/loginForm?redirect=' . $request->url());
            }
        }

        // Check if the user is logged in
        if (!Auth::user()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([], 401);
            }

            return redirect(env('IDP_BASE_URL') . '/loginForm?redirect=' . $request->url());
        }

        return $next($request);
    }

    /**
     * Returns a parsed array of attributes
     */
    protected function parseAttributes(object $attributes): array
    {
        $parsedAttributes = [];

        foreach ($attributes as $key => $value) {
            $parsedAttributes[] = (object)[
                "key" => $key,
                "value" => $value
            ];
        }
        return $parsedAttributes;
    }

    /**
     * Returns a parsed array of roles
     */
    protected function parseRoles(array $roles): array
    {
        $parsedRoles = [];

        foreach ($roles as $role) {
            $parsedRoles[] = (object) [
                'roleId' => $role->role_id,
                'roleName' => $role->roleName,
                'departmentId' => $role->department_id,
                'departmentName' => null,
                'branchCode' => null
            ];
        }
        return $parsedRoles;
    }

    /**
     * Returns the array with permissions
     *
     * @param $userId
     * @param array $roles
     * @return array
     */
    protected function retrievePermissions($userId, array $roles)
    {
        $builder = DB::table('grants');

        foreach ($roles as $role) {
            $builder->orWhere(function ($query) use ($role) {
                $query
                    ->where('role_name', $role->roleName)
                    ->where(function ($query) use ($role) {
                        $query->where('department_name', $role->departmentName)->orWhere('department_name', null);
                    });
            });
        }

        return $builder->pluck('grant')->all();
    }

    /**
     * Returns a ZUser after adding extra parameters. Otherwise return $user
     *
     * @param $user
     */
    protected function addExtraParametersToUser(ZUser &$user) {}
}
