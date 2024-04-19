<?php

namespace Zanichelli\IdpExtension\Models;

use Illuminate\Contracts\Auth\Authenticatable;


class ZUser implements Authenticatable {

    public $id;
    public $username;
    public $email;
    public $token;
    public $isVerified;
    public $name;
    public $surname;
    public $isEmployee;
    public $createdAt;
    public $roles;
    public $permissions;
    public $attributes;

    private function __construct($id, $username, $email, $token, $isVerified, $name, $surname,
                                $isEmployee, $createdAt, $roles = [], $permissions = [], $attributes = []){
        $this->id = $id;
        $this->email = $email;
        $this->username = $username;
        $this->token = $token;
        $this->isVerified = $isVerified;
        $this->name = $name;
        $this->surname = $surname;
        $this->isEmployee = $isEmployee;
        $this->createdAt = $createdAt;
        $this->roles = $roles;
        $this->permissions = $permissions;
        $this->attributes = $attributes;
    }

    /**
     * Factory to create ZUser instance
     *
     * @param $id
     * @param $username
     * @param $email
     * @param $token
     * @param $isVerified
     * @param $name
     * @param $surname
     * @param $isEmployee
     * @param $createdAt
     * @param array $roles
     * @param array $permissions
     * @param array $attributes
     * @return ZUser
     */
    public static function create($id, $username, $email, $token, $isVerified, $name, $surname,
                                  $isEmployee, $createdAt, $roles = [], $permissions = [], $attributes = []){
        return new self($id, $username, $email, $token, $isVerified, $name, $surname, $isEmployee, $createdAt, $roles, $permissions, $attributes);
    }


    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName(){
        return 'username';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier(){
        return $this->username;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(){
        return null;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken(){
        return $this->token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value){
        $this->token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(){
        return 'token';
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'password';
    }

}