<?php
require_once __DIR__ . "/../orm/model.php";

class UserModel extends model {

    public static $table = 'user';

    public static $fields = [
        'id' => 'id',
        'name' => 'name',
        'age' => 'age'
    ];

    public static $primaryKey = 'id';
}
