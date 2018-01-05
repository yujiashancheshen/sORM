<?php
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_WARNING);
//error_reporting(E_ALL);

require_once __DIR__ . "/model/UserModel.php";
require_once __DIR__ . "/orm/db.php";

$condition = [
 'select' => '*',
 'where' => [
      'id' => 1,
      'id >=' => 1,
      'id is not null' => null,
      'or id' => 1,
      'or id' => [1,2], 
  ],
 'limit' => 1,
 'group by' => 'id',
 'order by' => 'id',
];

$where = [
     'id' => 1,
     'id >=' => 1,
     'id is not null' => null,
     'or id' => 1,
     'or id' => [1,2], 
];

$insertData1 = [
	'name' => 'wangzhuo',
	'age' => 30
];

$insertData2 = [
	[
		'name' => 'wangzhuo',
		'age' => 13
	],
	[
	    'name' => 'wangzhuo',
	    //'age' => 23
	]
];

$updateData = [
	'age' => 0
];
$where2 = [
	'id >' => 7 
];

//$data = UserModel::select($condition);
//$data = UserModel::update($where2, $updateData);
//$data = UserModel::insert($insertData2);
//$data = UserModel::delete($where);
//$data = UserModel::count($where2);

//$user = UserModel::findByAge(23);
//
//var_dump($user);
//var_dump($user->id);
//
//var_dump(UserModel::getLastQuery());

$user = new UserModel();
var_dump($user);
$user->id = 11;
$user->name = '汪卓';
$user->age = 51;
var_dump($user);
$user->save();
var_dump($user);
