<?php

namespace Zanichelli\IdpExtension\Middleware;

use Closure;
use Zanichelli\IdpExtension\Models\Grant;
use Zanichelli\IdpExtension\Models\ZUser;
use Zanichelli\IdpExtension\Middleware\IdpMiddleware as IDP;

class IdpAuthMiddleware extends IDP
{
    /**
     * Returns the array with permissions
     *
     * @param $userId
     * @param array $roles
     * @return array
     */
    protected function retrievePermissions($userId, array $roles)
    {
        $permissions = [];
        foreach ($roles as $role) {
            $permission = Grant::where('role_id', $role->roleId)
                ->where('department_id', $role->departmentId)
                ->pluck('grant')->toArray();
            $permissions = array_merge($permissions, $permission);
        }
        return $permissions;
    }
    /**
     * Returns a ZUser after adding extra parameters. Otherwise return $user
     *
     * @param $user
     * @return ZUser
     */
    protected function addExtraParametersToUser(ZUser &$user)
    {
        //
    }
}