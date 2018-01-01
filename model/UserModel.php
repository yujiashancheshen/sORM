<?php
require_once __DIR__ . "/../orm/model.php";

class UserModel extends Model {

    public static $table = 'user';

    public static $primaryKey = 'id';

    public static $fields = [
        'id' => 'id',
        'name' => 'name',
        'age' => 'age',
    ];
}
