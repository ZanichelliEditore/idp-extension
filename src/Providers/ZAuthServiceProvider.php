<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 27/09/18
 * Time: 10.10
 *
 * @author Andrea De Castri
 */

use GuzzleHttp\Client;
use Illuminate\Contracts\Auth\UserProvider;
use Zanichelli\IdentityProvider\Models\ZUser;


class ZAuthServiceProvider implements UserProvider {

    public function __construct(){
        // TODO: Implement construct() method
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier){
        // TODO: Implement retrieveById() method.
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token){
        // TODO: Implement retrieveByToken() method.
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(\Illuminate\Contracts\Auth\Authenticatable $user, $token){
        // TODO: Implement updateRememberToken() method.
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials){

        if(empty($credentials) || empty($credentials['username']) || empty($credentials['password'])){
            return null;
        }

        $client = new Client();

        try {
            $response = $client->post('/v1/login', [
                'body' => [
                    'username' => $credentials['username'],
                    'password' => $credentials['password']
                ],
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if($response->getStatusCode() !== 200){
                return null;
            }

            $data = \GuzzleHttp\json_decode($response->getBody());

            $user = $data->user;
            $token = $data->token;

            // TODO vedere ruoli (easy), permessi (meno easy)
            return ZUser::create($user->id, $user->username, $user->email, $token, $user->is_verified, $user->name,
                $user->surname, $user->is_employee, $user->created_at, [], []);

        } catch (Exception $e){
            return null;
        }

    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials){
        // TODO: Implement validateCredentials() method.
    }

}