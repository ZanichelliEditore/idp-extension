<?php

/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 27/09/18
 * Time: 10.10
 *
 * @author Andrea De Castri
 */

namespace Zanichelli\IdentityProvider\Providers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Auth\UserProvider;
use Zanichelli\IdentityProvider\Models\ZUser;
use Zanichelli\Models\ZTrait\ZUserBuilder;


class ZAuthServiceProvider implements UserProvider
{

    use ZUserBuilder;

    public function __construct()
    {
        // Do nothing
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(\Illuminate\Contracts\Auth\Authenticatable $user, $token)
    {
        // Do nothing
    }

    /**
     * Retrieve a user by the given credentials, without his permissions.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {

        if (empty($credentials) || empty($credentials['username']) || empty($credentials['password'])) {
            return null;
        }

        $client = new Client();

        try {
            $response = $client->post(env('IDP_LOGIN_URL'), [
                'body' => [
                    'username' => $credentials['username'],
                    'password' => $credentials['password']
                ],
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = \GuzzleHttp\json_decode($response->getBody());

            $user = $data->user;
            $token = $data->token;

            $roles = $this->createRoleArray($user->roles);

            return ZUser::create(
                $user->id,
                $user->username,
                $user->email,
                $token,
                $user->is_verified,
                $user->name,
                $user->surname,
                $user->is_employee,
                $user->created_at,
                $roles
            );
        } catch (Exception $e) {
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
    public function validateCredentials(\Illuminate\Contracts\Auth\Authenticatable $user, array $credentials)
    {
        return false;
    }

    public function logout($token)
    {
        $client = new Client();

        try {
            $response = $client->get(env('IDP_LOGOUT_URL'), [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }
        } catch (RequestException $e) {
            if (!$e->hasResponse()) {
                return false;
            }

            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 403) {
                return true;
            }

            return false;
        }

        return true;
    }
}
