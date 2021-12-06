<?php

function mysort($a, $b): int
{
    global $db;
    if (strtolower($db[substr(basename($a), 0, -4)][$_GET['sortby']]) == strtolower($db[substr(basename($b), 0, -4)][$_GET['sortby']])) {
        return 0;
    }
    if (strtolower($db[substr(basename($a), 0, -4)][$_GET['sortby']]) > strtolower($db[substr(basename($b), 0, -4)][$_GET['sortby']])) {
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

            $user = substr(basename($file), 0, -4);

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
        "slopeLabel5" => false,
        "slopeLabel6" => false,
        "slopeLabel7" => false,
        "slopeLabel8" => false,
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

function generateTasks(&$industryData)
{
    $tasks = array();
    foreach ($industryData as $industry) {
        if ($industry['Type'] == 2) {
            $lumber = $industry['ProductsStored'][0];
            $beams = $industry['ProductsStored'][1];
            $SMlogsNeeded = $industry['EductsStored'][0];
        }
        if ($industry['Type'] == 3) {
            $iron = $industry['ProductsStored'][0];
            $rails = $industry['ProductsStored'][1];
            $SMcordwoodNeeded = $industry['EductsStored'][0];
            $SMironOreNeeded = $industry['EductsStored'][1];
        }
        if ($industry['Type'] == 4) {
            $pipes = $industry['ProductsStored'][0];
            $tools = $industry['ProductsStored'][1];
            $IWcoalNeeded = $industry['EductsStored'][1];
            $IWironNeeded = $industry['EductsStored'][0];
        }
        if ($industry['Type'] == 5) {
            $oilR = $industry['ProductsStored'][0];
            $OFpipesNeeded = $industry['EductsStored'][0];
            $OFbeamsNeeded = $industry['EductsStored'][1];
            $OFtoolsNeeded = $industry['EductsStored'][2];
        }
        if ($industry['Type'] == 6) {
            $oilB = $industry['ProductsStored'][0] + $industry['ProductsStored'][1];
            $REFlumberNeeded = $industry['EductsStored'][2];
            $REFpipesNeeded = $industry['EductsStored'][1];
            $REFoilNeeded = $industry['EductsStored'][0];
        }
        if ($industry['Type'] == 7) {
            $ironOre = $industry['ProductsStored'][0];
            $IMlumberNeeded = $industry['EductsStored'][0];
            $IMbeamsNeeded = $industry['EductsStored'][1];
        }
        if ($industry['Type'] == 8) {
            $coal = $industry['ProductsStored'][0];
            $CMbeamsNeeded = $industry['EductsStored'][0];
            $CMrailsNeeded = $industry['EductsStored'][1];
        }
    }
    if ($oilB >= 46) {
        $x = floor(($$oilB) / 46);
        if ($x) {
            $reward = 1840 * $x;
            $tasks[] = 'Sell ' . $x . ' carts of oil from refinery to Freight depot. ($' . $reward . ')';
        }
    }
    if ((24 - $REFlumberNeeded) <= $lumber) {
        $x = min($REFpipesNeeded, $REFoilNeeded) + (24 - $REFlumberNeeded);
        if (floor($x / 6)) {
            $reward = 72 * floor($x / 6);
            $tasks[] = 'Deliver ' . floor(($x) / 6) . ' carts of lumber from saw mill to refinery. ($' . $reward . ')';
            $lumber -= $x;
        }
    }
    if ((100 - $REFoilNeeded) <= $oilR) {
        $x = min($REFpipesNeeded, $REFlumberNeeded) + (100 - $REFoilNeeded);
        if (floor($x / 12)) {
            $reward = 192 * floor($x / 12);
            $tasks[] = 'Deliver ' . floor(($x) / 12) . ' carts of raw oil from oil field to refinery. ($' . $reward . ')';
        }
    }
    if ((100 - $REFpipesNeeded) <= $pipes) {
        $x = min($REFoilNeeded, $REFlumberNeeded) + (100 - $REFpipesNeeded);
        if (floor($x / 9)) {
            $reward = 180 * floor($x / 9);
            $tasks[] = 'Deliver ' . floor(($x) / 9) . ' carts of pipes from iron works to refinery. ($' . $reward . ')';
            $pipes -= $x;
        }
    }
    if ((18 - $OFpipesNeeded) <= $pipes) {
        $x = min($OFbeamsNeeded, $OFtoolsNeeded) + (18 - $OFpipesNeeded);
        if (floor($x / 9)) {
            $reward = 180 * floor($x / 9);
            $tasks[] = 'Deliver ' . floor(($x) / 9) . ' carts of pipes from iron works to oil field. ($' . $reward . ')';
            $pipes -= $x;
        }
    }
    if ((20 - $OFbeamsNeeded) <= $beams) {
        $x = min($OFpipesNeeded, $OFtoolsNeeded) + (20 - $OFbeamsNeeded);
        if (floor($x / 3)) {
            $reward = 36 * floor($x / 3);
            $tasks[] = 'Deliver ' . floor(($x) / 3) . ' carts of beams from saw mill to oil field. ($' . $reward . ')';
            $beams -= $x;
        }
    }
    if ((100 - $OFtoolsNeeded) <= $tools) {
        $x = min($OFbeamsNeeded, $OFpipesNeeded) + (100 - $OFtoolsNeeded);
        if (floor($x / 32)) {
            $reward = 640 * floor($x / 32);
            $tasks[] = 'Deliver ' . floor(($x) / 32) . ' carts of tools from iron works to oil field. ($' . $reward . ')';
            $tools -= $x;
        }
    }
    if ((100 - $IWcoalNeeded) <= $coal) {
        $x = $IWironNeeded + (100 - $IWcoalNeeded);
        if (floor($x / 10)) {
            $reward = 150 * floor($x / 10);
            $tasks[] = 'Deliver ' . floor(($x) / 10) . ' hoppers of coal from coal mine to iron works. ($' . $reward . ')';
        }
    }
    if ((100 - $IWironNeeded) <= $iron) {
        $x = $IWcoalNeeded + (100 - $IWironNeeded);
        if (floor($x / 10)) {
            $reward = 54 * floor($x / 10);
            $tasks[] = 'Deliver ' . floor(($x) / 10) . ' carts of iron from smelter to iron works. ($' . $reward . ')';
        }
    }
    if ((100 - $SMcordwoodNeeded) >= 8) {
        $x = floor((100 - $SMcordwoodNeeded) / 8);
        if ($x) {
            $reward = 80 * $x;
            $tasks[] = 'Deliver ' . ($x) . ' carts of cordwood from lumber camp to smelter. ($' . $reward . ')';
        }
    }
    if ((100 - $SMironOreNeeded) <= $ironOre) {
        $x = $SMcordwoodNeeded + (100 - $SMironOreNeeded);
        if (floor($x / 10)) {
            $reward = 140 * floor($x / 10);
            $tasks[] = 'Deliver ' . floor(($x) / 10) . ' hoppers of iron ore from iron mine to smelter. ($' . $reward . ')';
        }
    }
    if ((20 - $IMbeamsNeeded) <= $beams) {
        $x = $IMlumberNeeded + (20 - $IMbeamsNeeded);
        if (floor($x / 3)) {
            $reward = 36 * floor($x / 3);
            $tasks[] = 'Deliver ' . floor(($x) / 3) . ' carts of beams from saw mill to iron ore mine. ($' . $reward . ')';
            $beams -= $x;
        }
    }
    if ((24 - $IMlumberNeeded) <= $lumber) {
        $x = $IMbeamsNeeded + (24 - $IMlumberNeeded);
        if (floor($x / 6)) {
            $reward = 72 * floor($x / 6);
            $tasks[] = 'Deliver ' . floor(($x) / 6) . ' carts of lumber from saw mill to iron ore mine. ($' . $reward . ')';
            $lumber -= $x;
        }
    }
    if ((20 - $CMbeamsNeeded) <= $beams) {
        $x = $CMrailsNeeded + (20 - $CMbeamsNeeded);
        if (floor($x / 3)) {
            $reward = 36 * floor($x / 3);
            $tasks[] = 'Deliver ' . floor(($x) / 3) . ' carts of beams from saw mill to coal mine. ($' . $reward . ')';
            $beams -= $x;
        }
    }
    if ((50 - $CMrailsNeeded) <= $rails) {
        $x = $CMbeamsNeeded + (50 - $CMrailsNeeded);
        if (floor($x / 10)) {
            $reward = 180 * floor($x / 10);
            $tasks[] = 'Deliver ' . floor(($x) / 10) . ' carts of rails from smelter to coal mine. ($' . $reward . ')';
        }
    }
    if ($SMlogsNeeded < 100) {
        $x = 100 - $SMlogsNeeded;
        if (floor($x / 6)) {
            $reward = 60 * floor($x / 6);
            $tasks[] = 'Deliver ' . floor(($x) / 6) . ' carts of logs from lumber camp to saw mill. ($' . $reward . ')';
        }
    }

    return $tasks;
}
//    name = "Logging Camp";
//            pos = ['logs_p.svg', 'cordwood_p.svg', 'cordwood_p.svg', 'logs_p.svg'];
//
//            name = 'Sawmill';
//            pos = ['lumber_p.svg', 'beams_p.svg'];
//            pis = ['logs_p.svg'];
//
//            name = 'Smelter';
//            pos = ['iron_p.svg', 'rails_p.svg'];
//            pis = ['cordwood_p.svg', 'ironore_p.svg'];
//
//            name = 'Ironworks';
//            pos = ['pipes_p.svg', 'tools_p.svg'];
//            pis = ['iron_p.svg', 'coal_p.svg'];
//
//            name = 'Oilfield';
//            pis = ['pipes_p.svg', 'beams_p.svg', 'tools_p.svg'];
//            pos = ['oil_p.svg'];
//
//            name = 'Refinery';
//            pis = ['oil_p.svg', 'pipes_p.svg', 'lumber_p.svg'];
//            pos = ['barrels_p.svg', 'barrels_p.svg'];
//
//            name = 'Coal Mine';
//
//            name = 'Iron Mine';
//
//            name = 'Freight Depot';
//
//    name = 'F#' + index;
//            pis = ['cordwood_p.svg'];
