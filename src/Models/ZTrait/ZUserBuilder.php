<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 27/09/18
 * Time: 16.17
 */

namespace Zanichelli\Models\ZTrait;


use Zanichelli\IdentityProvider\Models\ZRole;

trait ZUserBuilder {

    /**
     * Returns an array of ZRole
     *
     * @param array $roles
     * @return array
     */
    protected function createRoleArray(array $roles){
        $result = [];

        foreach ($roles as $role){
            if(empty($role->department)){
                $result[] = ZRole::create($role->role->id, $role->role->name, null, null);
            } else {
                $result[] = ZRole::create($role->role->id, $role->role->name, $role->department->id, $role->department->name);
            }
        }

        return $result;
    }

}