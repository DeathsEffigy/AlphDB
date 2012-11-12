<?php
require_once './alphdb.class.latest.php';

$db = new AlphDB('test', 'deathseffigy', 'example');
$db->remove('my_users', array(array('strNick', 'DeathsEffigy'), array('strNick', 'Fabian')));
?>