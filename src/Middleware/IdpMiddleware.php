<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 26/09/18
 * Time: 16.16
 *
 * @author Andrea De Castri
 */

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zanichelli\IdentityProvider\Models\ZRole;
use Zanichelli\IdentityProvider\Models\ZUser;

abstract class IdpMiddleware {

    public function handle(Request $request, Closure $next){

        // Check if the request has the token field
        if($request->input('token')){
            $token = $request->input('token');

            $client = new Client(['verify' => false]);
            $response = $client->get(env('IDP_TOKEN') . '?token=' . $token);

            if($response->getStatusCode() == 200){

                $userJson = json_decode($response->getBody());

                $roles = $this->createRoleArray($userJson->roles);

                $permissions = $this->retrievePermissions($userJson->id);

                $user = ZUser::create($userJson->id, $userJson->username, $userJson->email, $token, $userJson->is_verified, $userJson->name,
                    $userJson->surname, $userJson->is_employee, $userJson->created_at, $roles, $permissions);

                $user = $this->addExtraParametersToUser($user);

                Auth::setUser($user);
            }
        }

        // Check if the user is logged in
        if(!Auth::user()){
            return redirect(env('IDP_URL') . '?redirect=' . $request->url());
        }

        return $next($request);
    }

    private function createRoleArray(array $roles){
        $result = [];

        foreach ($roles as $role){
            if(empty($role->department)){
                $roles[] = ZRole::create($role->role->id, $role->role->name, null, null);
            } else {
                $roles[] = ZRole::create($role->role->id, $role->role->name, $role->department->id, $role->department->name);
            }
        }

        return $result;
    }

    /**
     * Returns the array with permissions
     *
     * @param int userId
     * @return array
     */
    protected abstract function retrievePermissions($userId);

    /**
     * Returns a ZUser after adding extra parameters. Otherwise return $user
     *
     * @param $user
     * @return ZUser
     */
    protected abstract function addExtraParametersToUser($user);

}