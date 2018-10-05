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
            $result[] = ZRole::create($role->roleId, $role->roleName, $role->departmentId, $role->departmentName);
        }

        return $result;
    }

}