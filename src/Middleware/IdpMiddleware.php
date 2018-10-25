<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 26/09/18
 * Time: 16.16
 *
 * @author Andrea De Castri
 */

namespace Zanichelli\IdentityProvider\Middleware;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zanichelli\IdentityProvider\Models\ZUser;
use Zanichelli\Models\ZTrait\ZUserBuilder;

abstract class IdpMiddleware {

    use ZUserBuilder;

    public function handle(Request $request, Closure $next){

        // Check if the request has the token field
        if($request->input('token')){
            $token = $request->input('token');

            $client = new Client(['verify' => false]);
            $response = $client->get(env('IDP_TOKEN_URL') . '?token=' . $token);

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
    protected abstract function retrievePermissions($userId, array $roles);

    /**
     * Returns a ZUser after adding extra parameters. Otherwise return $user
     *
     * @param $user
     */
    protected abstract function addExtraParametersToUser(ZUser &$user);

}