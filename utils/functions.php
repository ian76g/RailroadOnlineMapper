<?php

function mysort($a, $b): int
{
    global $db;
    if (strtolower($db[substr(basename($a),0,-4)][$_GET['sortby']]) == strtolower($db[substr(basename($b),0,-4)][$_GET['sortby']])) {
        return 0;
    }
    if (strtolower($db[substr(basename($a),0,-4)][$_GET['sortby']]) > strtolower($db[substr(basename($b),0,-4)][$_GET['sortby']])) {
        $x = -1;
    } else {
        $x = 1;
    }
    if ($_GET['sortorder'] == 'desc') {
        return $x;
    } else {
        return -$x;
    }
}

function list_sav_files(): array
{
    $files = [];
    $directory = 'saves/';
    $dh = opendir($directory);
    while ($file = readdir($dh)) {
        if (substr($file, -4) == '.sav') {
            $files[filemtime($directory . $file)] = $directory . $file;
        }
    }
    return $files;
}

function map_entries($sortby = null, $sortorder = null): Generator
{
    $file_limit = 1600;
    $files = list_sav_files();
    global $db;

    if ((isset($files) && $files != null) && file_exists('db.db')) {
        $db = unserialize(file_get_contents('db.db'));

        if (!$sortorder) {
            krsort($files);
        } else {
            usort($files, 'mysort');
        }

        $i = 0;
        foreach ($files as $file) {
            if (!$file) break;

            if ($i > $file_limit) {
//                unlink($file);
            }

            $user = substr(basename($file),0,-4);

            yield array(
                "filename" => $file,
                "name" => pathinfo($file, PATHINFO_FILENAME),
                "trackLength" => round($db[$user][0] / 100000, 2),
                "numY" => ($db[$user][1] == null ? '0' : $db[$user][1]),
                "numT" => ($db[$user][6] == null ? '0' : $db[$user][6]),
                "numLocs" => ($db[$user][2] == null ? '0' : $db[$user][2]),
                "numCarts" => ($db[$user][3] == null ? '0' : $db[$user][3]),
                "slope" => round(($db[$user][4] == null ? '0' : $db[$user][4])),
                "public" => $db[$user][7],
            );
            $i++;
        }
    }
}

function checked_if_true_or_default($name)
{
    $defaults = array(
        "trees_default" => false,
        "trees_user" => false,
        "beds" => true,
        "tracks" => true,
        "switches" => true,
        "rollingstock" => true,
        "slopeLabel0" => false,
        "slopeLabel1" => false,
        "slopeLabel2" => true,
        "slopeLabel3" => true,
        "slopeLabel4" => false,
        "maxSlopeLabel" => true,
        "ironOverWood" => false
    );

    if (!isset($_COOKIE[$name])) {
        if ($defaults[$name] === true) {
            print("checked");
        }
        return;
    }

    if (isset($_COOKIE[$name]) && filter_var($_COOKIE[$name], FILTER_VALIDATE_BOOLEAN) === true) {
        print("checked");
    }
}

function color_cookie_or_default($name)
{
    if (!isset($_COOKIE[$name])) {
        print("#0000ff");
    }

    if (isset($_COOKIE[$name])) {
        print($_COOKIE[$name]);
    }
}