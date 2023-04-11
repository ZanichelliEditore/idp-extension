<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Zanichelli\IdpExtension\Models\Grant;
use Zanichelli\IdpExtension\Models\Mongodb\Grant as MongoGrant;
use Zanichelli\IdpExtension\Models\ZUser;
use Zanichelli\IdpExtension\Models\ZTrait\ZUserBuilder;

class IdpMiddleware
{

    use ZUserBuilder;

    public function handle(Request $request, Closure $next)
    {

        // Check if the request has the token field
        if ($request->input('token')) {
            $token = $request->input('token');

            $client = new Client(['verify' => false]);

            try {
                $response = $client->get(env('IDP_TOKEN_URL'), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([], 401);
                }

                return redirect(env('IDP_URL') . '?redirect=' . $request->url());
            }

            if ($response->getStatusCode() == 200) {

                $userJson = \GuzzleHttp\Utils::jsonDecode($response->getBody());

                $roles = $this->createRoleArray($userJson->roles);

                $attributes = $this->createAttributeArray($userJson->attributes);

                $permissions = $this->retrievePermissions($userJson->id, $roles);

                $user = ZUser::create(
                    $userJson->id,
                    $userJson->username,
                    $userJson->email,
                    $token,
                    $userJson->is_verified,
                    $userJson->name,
                    $userJson->surname,
                    $userJson->is_employee,
                    $userJson->created_at,
                    $roles,
                    $permissions,
                    $attributes,
                    $userJson->is_myzanichelli
                );

                $this->addExtraParametersToUser($user);

                Auth::setUser($user);
            }
        }

        // Check if the user is logged in
        if (!Auth::user()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([], 401);
            }

            return redirect(env('IDP_URL') . '?redirect=' . $request->url());
        }

        return $next($request);
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
        $permissions = [];
        $grant = new Grant();
        if (config('idp.connection') === 'mongodb') {
            $grant = new MongoGrant();
        }
        foreach ($roles as $role) {
            $permission = $grant::where('role_name', $role->roleName)
                ->where(function ($query) use ($role) {
                    $query->where('department_name', $role->departmentName)
                        ->orWhere('department_name', null);
                })
                ->pluck('grant')->toArray();
            $permissions = array_merge($permissions, $permission);
        }

        return $permissions;
    }

    /**
     * Returns a ZUser after adding extra parameters. Otherwise return $user
     *
     * @param $user
     */
    protected function addExtraParametersToUser(ZUser &$user)
    {
    }
}
