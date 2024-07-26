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

class IdpMiddleware
{
    use ZUserBuilder;

    public function handle(Request $request, Closure $next, string $withPermissions = 'with_permissions')
    {
        if ($token = $request->input('token')) {
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

                $permissions = [];
                if ($withPermissions !== 'without_permissions') {
                    $permissions = $this->retrievePermissions($userJson->id, $roles);
                }

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
                    $attributes
                );

                $this->addExtraParametersToUser($user);

                Auth::setUser($user);

                if ($request->query('token')) {
                    $request->query->remove('token');
                    return redirect($request->url());
                }
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

        return $builder->pluck('grant');
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
