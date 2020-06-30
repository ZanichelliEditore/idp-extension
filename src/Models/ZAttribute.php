<?php

namespace Zanichelli\IdpExtension\Models;

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