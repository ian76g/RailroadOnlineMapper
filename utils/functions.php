<?php

function mysort($a, $b): int
{
    global $db;
    if (strtolower($db[substr($a, 5, -5) . '.sav'][$_GET['sortby']]) == strtolower($db[substr($b, 5, -5) . '.sav'][$_GET['sortby']])) {
        return 0;
    }
    if (strtolower($db[substr($a, 5, -5) . '.sav'][$_GET['sortby']]) > strtolower($db[substr($b, 5, -5) . '.sav'][$_GET['sortby']])) {
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

    if ((isset($files) && $files != null) && file_exists('db.db')) {
        $db = unserialize(file_get_contents('db.db'));

        if (!$sortby || !$sortorder) {
            krsort($files);
        } else {
            usort($files, 'mysort');
        }

        $i = 0;
        foreach ($files as $file) {
            if (!$file) break;

            if ($i > $file_limit) {
                unlink($file);
            }

            yield array(
                "filename" => $file,
                "name" => pathinfo($file, PATHINFO_FILENAME),
                "trackLength" => round($db[$file][0] / 100000, 2),
                "numY" => ($db[$file][1] == null ? '0' : $db[$file][1]),
                "numT" => ($db[$file][6] == null ? '0' : $db[$file][6]),
                "numLocs" => ($db[$file][2] == null ? '0' : $db[$file][2]),
                "numCarts" => ($db[$file][3] == null ? '0' : $db[$file][3]),
                "slope" => round(($db[$file][4] == null ? '0' : $db[$file][4]))
            );
            $i++;
        }
    }
}
