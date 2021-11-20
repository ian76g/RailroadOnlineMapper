<?php
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
$db = unserialize(file_get_contents('db.db'));
if (getUserIpAddr() != $db[$mapName][5] && $db[$mapName][7]!=true) {
    die('This is nor your save game!');
}
header('Content-Disposition: attachment; filename="'.$mapName.'.sav"');
readfile('saves/'.$mapName.'.sav');