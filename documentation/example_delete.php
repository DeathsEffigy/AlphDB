<?php
require_once './alphdb.class.latest.php';

$db = new AlphDB('test', 'deathseffigy', 'example');
$db->delete();
?>