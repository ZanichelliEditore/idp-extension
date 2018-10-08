<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 21/09/18
 * Time: 15.50
 *
 * @author Andrea De Castri
 */

namespace Zanichelli\IdentityProvider\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Zanichelli\IdentityProvider\Providers\ZAuthServiceProvider;


class ZGuard implements Guard, StatefulGuard {

    private $session;

    private $user;

    /** @var ZAuthServiceProvider  */
    private $provider;

    private function __construct(Session $session, UserProvider $provider){
        $this->session = $session;
        $this->provider = $provider;
    }

    /**
     * Factory to create ZGuard instance
     *
     * @param Session $session
     * @param UserProvider $provider
     * @return ZGuard
     */
    public static function create(Session $session, UserProvider $provider){
        return new self($session, $provider);
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(){
        return $this->session->exists('user');
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(){
        return !$this->session->exists('user');
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user(){

        if($this->user){
            return $this->user;
        }

        return $this->session->get('user');
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id(){

        if($this->user){
            return $this->user->id;
        }

        return null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = []){
        return false;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public function setUser(Authenticatable $user){

        $this->session->put('user', $user);

        $this->user = $user;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array $credentials
     * @param  bool $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false){

        $user = $this->provider->retrieveByCredentials($credentials);

        $this->session->put('user', $user);

        $this->user = $user;

        return !is_null($user);
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array $credentials
     * @return bool
     */
    public function once(array $credentials = []){
        return false;
    }

    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  bool $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false){
        // Do nothing
    }

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed $id
     * @param  bool $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function loginUsingId($id, $remember = false){
        return null;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param  mixed $id
     * @return bool
     */
    public function onceUsingId($id){
        return false;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember(){
        return false;
    }

    /**
     * Log the user out of the application.
     *
     * @return bool
     */
    public function logout(){

        if(!$this->provider instanceof ZAuthServiceProvider){
            return false;
        }

        if($this->provider->logout($this->user->token)){

            $this->user = null;

            $this->session->flush();

            return true;
        }

        return false;
    }

}