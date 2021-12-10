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

if (getUserIpAddr() != $ip[0]['ip'] && $ip[0]['unsused']!=true) {
    die('This is nor your save game!');
}
header('Content-Disposition: attachment; filename="'.$mapName.'.sav"');
readfile('saves/'.$mapName.'.sav');