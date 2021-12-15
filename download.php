<?php
require_once 'utils/functions.php';

function getUserIpAddr()
{
    global $argv;

    if (isset($argv) && $argv) {
        return 'local';
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
$mapName = $_GET['map'];
connect();
$ip = query('select ip, unused from stats where name="'.mysqli_real_escape_string($dbh, $mapName).'"');

if (getUserIpAddr() != $ip[0]['ip'] && $ip[0]['unused']!='on') {
    die('This is nor your save game!');
}
$x= query('select * from downloads where name="'.mysqli_real_escape_string($dbh, $mapName).'"');
if(!isset($x[0])){
    query('insert into downloads values("'.mysqli_real_escape_string($dbh, $mapName).'", 1)');
} else {
    query('update downloads set downloads=downloads+1 where name="'.mysqli_real_escape_string($dbh, $mapName).'"');
}
header('Content-Disposition: attachment; filename="'.$mapName.'.sav"');
readfile('saves/'.$mapName.'.sav');