<?php

namespace Zanichelli\IdpExtension\ZTrait;


use Zanichelli\IdpExtension\Models\ZRole;
use Zanichelli\IdpExtension\Models\ZAttribute;

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