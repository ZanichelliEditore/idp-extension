<?php
/**
 * Created by PhpStorm.
 * User: andreadecastri
 * Date: 25/10/18
 * Time: 14.10
 */

namespace Zanichelli\Models;


class ZAttribute {

    public $key;
    public $value;

    private function __construct($key, $value){
        $this->key = $key;
        $this->value = $value;
    }

    public static function create($key, $value){
        return new ZAttribute($key, $value);
    }

}