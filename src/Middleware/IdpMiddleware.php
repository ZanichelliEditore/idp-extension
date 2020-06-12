<?php

namespace Zanichelli\IdpExtension\Middleware;

use Closure;
use App\Models\Grant;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Zanichelli\IdpExtension\Models\ZUser;
use Zanichelli\IdpExtension\Models\ZTrait\ZUserBuilder;

abstract class IdpMiddleware {

    use ZUserBuilder;

    public function handle(Request $request, Closure $next){

        // Check if the request has the token field
        if($request->input('token')){
            $token = $request->input('token');

            $client = new Client(['verify' => false]);

            try {
                $response = $client->get(env('IDP_TOKEN_URL'), [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
            } catch (\Exception $e){
                Log::error($e->getMessage());
                return redirect(env('IDP_URL') . '?redirect=' . $request->url());
            }

            if($response->getStatusCode() == 200){

                $userJson = \GuzzleHttp\json_decode($response->getBody());

                $roles = $this->createRoleArray($userJson->roles);

                $attributes = $this->createAttributeArray($userJson->attributes);

                $permissions = $this->retrievePermissions($userJson->id, $roles);

                $user = ZUser::create($userJson->id, $userJson->username, $userJson->email, $token, $userJson->is_verified, $userJson->name,
                    $userJson->surname, $userJson->is_employee, $userJson->created_at, $roles, $permissions, $attributes);

                $this->addExtraParametersToUser($user);

                Auth::setUser($user);
            }
        }

        // Check if the user is logged in
        if(!Auth::user()){
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
        foreach ($roles as $role) {
            $permission = Grant::where('role_id', $role->roleId)
                ->where('department_id', null)
                ->orWhere(function ($query) use ($role) {
                    $query->where('department_id', $role->departmentId)
                        ->where('role_id', $role->roleId);
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
    protected abstract function addExtraParametersToUser(ZUser &$user);

}