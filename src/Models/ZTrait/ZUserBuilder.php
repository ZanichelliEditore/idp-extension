<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 27/09/18
 * Time: 16.17
 */

namespace Zanichelli\Models\ZTrait;


use Zanichelli\IdentityProvider\Models\ZRole;
use Zanichelli\Models\ZAttribute;

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
            $result[] = ZRole::create($role->roleId, $role->roleName, $role->departmentId, $role->departmentName, $role->branchCode);
        }

        return $result;
    }

    /**
     * Returns an array of ZAttribute
     *
     * @param array $attributes
     * @return array
     */
    protected function createAttributeArray(array $attributes){
        $result = [];

        foreach ($attributes as $attribute){
            $result[] = ZAttribute::create($attribute->key, $attribute->value);
        }

        return $result;
    }

}