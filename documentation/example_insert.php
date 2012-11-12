<?php
require_once './alphdb.class.latest.php';

$db = new AlphDB('test', 'deathseffigy', 'example');
$db->insert('my_users', array('strNick' => 'DeathsEffigy'));
$db->insert('my_users', array('strNick' => 'Fabian'));
?>