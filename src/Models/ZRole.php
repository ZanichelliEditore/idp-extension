<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 21/09/18
 * Time: 16.11
 *
 * @author Andrea De Castri
 */

namespace Zanichelli\IdentityProvider\Models;


class ZRole {

    public $roleId;
    public $roleName;
    public $departmentId;
    public $departmentName;
    public $branchCode;

    private function __construct($roleId, $roleName, $departmentId, $departmentName, $branchCode){
        $this->roleId = $roleId;
        $this->roleName = $roleName;
        $this->departmentId = $departmentId;
        $this->departmentName = $departmentName;
        $this->branchCode = $branchCode;
    }

    public static function create($roleId, $roleName, $departmentId, $departmentName, $branchCode){
        return new self($roleId, $roleName, $departmentId, $departmentName, $branchCode);
    }

}