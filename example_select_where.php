<?php
require_once './alphdb.class.latest.php';

$db = new AlphDB('test', 'deathseffigy', 'example');
$table = $db->select("my_users")->where(array(array("strNick", "my_nick")));
print_r($table);
?>