<?php
function plural($number)
{
    if ($number == 1) return '';
    return 's';
}

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
    global $dbh;

    if ((isset($files) && $files != null)) {


        if (!$sortorder) {
            krsort($files);
        } else {
            usort($files, 'mysort');
        }

        $files = array_merge(array('MINIZWERG-MAP.sav'), $files);
        $i = 0;
        foreach ($files as $file) {
            if (!$file) break;

            if ($i > $file_limit) {
//                unlink($file);
            }

            $user = substr(basename($file), 0, -4);
            connect();
            $row = query('select "' . mysqli_real_escape_string($dbh, $file) . '" as file, name, 
            round(length/100000,2) as trackLength, switches as numY, trees as numT, 
       locos as numLocs, carts as numCarts, round(slope) as slope, unused as public, tasksA as tasks, tasksAreward as reward
            from stats where name="' . mysqli_real_escape_string($dbh, $user) . '"');
            if (!isset($row[0])) {
                continue;
                $row[0] = array('file' => $file, 'name' => $user, 'trackLength' => '?',
                    'numLocs' => '?', 'numCarts' => '?', 'slope' => '?', 'public' => '', 'tasks' => '?', 'reward' => '?',
                    'numY' => '?', 'numT' => '?');

            }
            $x = query('select * from downloads where name="' . mysqli_real_escape_string($dbh, $user) . '"');
            if (!isset($x[0])) {
                $row[0]['downloads'] = 0;
            } else {
                $row[0]['downloads'] = $x[0]['downloads'];
            }
            yield $row[0];
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

    $default['ce_boxcar'] = '#66f081';
    $default['ce_flatcar_cordwood'] = '#fbf9bc';
    $default['ce_flatcar_hopper'] = '#a7a7a9';
    $default['ce_flatcar_logs'] = '#f99a9a';
    $default['ce_flatcar_stakes'] = '#fdbf77';
    $default['ce_flatcar_tanker'] = '#9090f9';
    $default['cf_boxcar'] = '#36cc00';
    $default['cf_flatcar_cordwood'] = '#ffeb0f';
    $default['cf_flatcar_hopper'] = '#000000';
    $default['cf_flatcar_logs'] = '#ff0000';
    $default['cf_flatcar_stakes'] = '#f38a12';
    $default['cf_flatcar_tanker'] = '#0000ff';

    if (!isset($_COOKIE[$name])) {
        echo $default[$name];
    }

    if (isset($_COOKIE[$name])) {
        print($_COOKIE[$name]);
    }
}

/**
 * @param $industryData
 * @param ArithmeticHelper $ah
 * @return array
 */
function generateTasks(&$industryData, ArithmeticHelper $ah, $industryTracks)
{
    global $coords;
    $cartTracks = $industryTracks[1];
    $industryTracks = $industryTracks[0];
    $tasks = array();
    $Btasks = array();
    $Stasks = array();
    $Rtasks = array();
    $fireDepots = array();
    $indWithoutFDs = array();
    foreach ($industryData['Industries'] as $industry) {
        if ($industry['Type'] == 1) {
            $coords['logging camp'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 2) {
            $sawmill = false;
            $lumber = $industry['ProductsStored'][0];
            $beams = $industry['ProductsStored'][1];
            $SMlogsNeeded = $industry['EductsStored'][0];
            $indWithoutFDs[] = $industry;
            if (isset($industryTracks[$industry['Type']])) {
                $sawmill = true;
            }
            $coords['sawmill'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 3) {
            $smelter = false;
            $iron = $industry['ProductsStored'][0];
            $rails = $industry['ProductsStored'][1];
            $SMcordwoodNeeded = $industry['EductsStored'][0];
            $SMironOreNeeded = $industry['EductsStored'][1];
            $indWithoutFDs[] = $industry;
            if (isset($industryTracks[$industry['Type']])) {
                $smelter = true;
            }
            $coords['smelter'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 4) {
            $ironworks = false;
            $pipes = $industry['ProductsStored'][0];
            $tools = $industry['ProductsStored'][1];
            $IWcoalNeeded = $industry['EductsStored'][1];
            $IWironNeeded = $industry['EductsStored'][0];
            $indWithoutFDs[] = $industry;
            if (isset($industryTracks[$industry['Type']])) {
                $ironworks = true;
            }
            $coords['iron works'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 5) {
            $oilfield = false;
            $oilR = $industry['ProductsStored'][0];
            $OFpipesNeeded = $industry['EductsStored'][0];
            $OFbeamsNeeded = $industry['EductsStored'][1];
            $OFtoolsNeeded = $industry['EductsStored'][2];
            $indWithoutFDs[] = $industry;
            if (isset($industryTracks[$industry['Type']])) {
                $oilfield = true;
            }
            $coords['oil field'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 6) {
            $refinery = false;
            $oilB = $industry['ProductsStored'][0] + $industry['ProductsStored'][1];
            $REFlumberNeeded = $industry['EductsStored'][2];
            $REFpipesNeeded = $industry['EductsStored'][1];
            $REFoilNeeded = $industry['EductsStored'][0];
            $indWithoutFDs[] = $industry;
            if (isset($industryTracks[$industry['Type']])) {
                $refinery = true;
            }
            $coords['refinery'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 8) {
            $ironmine = false;
            $ironOre = $industry['ProductsStored'][0];
            $IMlumberNeeded = $industry['EductsStored'][0];
            $IMbeamsNeeded = $industry['EductsStored'][1];
            $indWithoutFDs[] = $industry;
            if (isset($industryTracks[$industry['Type']])) {
                $ironmine = true;
            }
            $coords['iron mine'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 7) {
            $coalmine = false;
            $coal = $industry['ProductsStored'][0];
            $CMbeamsNeeded = $industry['EductsStored'][0];
            $CMrailsNeeded = $industry['EductsStored'][1];
            $indWithoutFDs[] = $industry;
            if (isset($industryTracks[$industry['Type']])) {
                $coalmine = true;
            }
            $coords['coal mine'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 9) {
            $indWithoutFDs[] = $industry;
            $coords['freight depot'] = array($industry['Location'][0], $industry['Location'][1]);
        }
        if ($industry['Type'] == 10) {
            $FDbeamsNeeded = $industry['EductsStored'][0];
            $near = $ah->nearestIndustry($industry['Location'], $indWithoutFDs);
            $fireDepots[] = array($FDbeamsNeeded, $near);
            $coords['firewood depot #' . (sizeof($fireDepots) + 8)] = array($industry['Location'][0], $industry['Location'][1]);
        }
    }
    $cartsCordwood = 0;
    $cartsStakes = 0;
    $cartsFlat = 0;
    $cartsTanker = 0;
    $cartsHopper = 0;
    $cartsBox = 0;

    if ($oilB >= 46) {
        $x = floor(($$oilB) / 46);
        if ($x) {
            $tasks[] = taskText($x, 'oil barrels', 'refinery', 'freight depot');
            $cartsCordwood += $x;
        }
    }
    if (min(6, (24 - $REFlumberNeeded)) <= $lumber) {
        $x = min($lumber, min($REFpipesNeeded, $REFoilNeeded) + (24 - $REFlumberNeeded));
        if (floor($x / 6)) {
            $reward = 72 * floor($x / 6);
            if ($refinery) {
                $tasks[] = taskText(floor(($x) / 6), 'lumber', 'sawmill', 'refinery');
                $lumber -= $x;
                $cartsStakes += floor(($x) / 6);
            } else {
                $Btasks['refinery'][] = array('Build a track to ' . insertLink('refinery') . '. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(12, (100 - min(100, $REFoilNeeded))) <= $oilR) {
        $x = min($oilR, min($REFpipesNeeded, $REFlumberNeeded) + (100 - min(100, $REFoilNeeded)));
        if (floor($x / 12)) {
            $reward = 192 * floor($x / 12);
            if ($refinery) {
                $tasks[] = taskText(floor(($x) / 12), 'crude oil', 'oil field', 'refinery');
                $cartsTanker += floor(($x) / 12);
            } else {
                $Btasks['refinery'][] = array('Build a track to refinery. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(9, (100 - $REFpipesNeeded)) <= $pipes) {
        $x = min($pipes, min($REFoilNeeded, $REFlumberNeeded) + (100 - $REFpipesNeeded));
        if (floor($x / 9)) {
            $reward = 180 * floor($x / 9);
            if ($refinery) {
                $tasks[] = taskText(floor(($x) / 9), 'steel pipes', 'iron works', 'refinery');
                $pipes -= $x;
                $cartsFlat += floor(($x) / 9);
            } else {
                $Btasks['refinery'][] = array('Build a track to refinery. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(9, (18 - $OFpipesNeeded)) <= $pipes) {
        $x = min($pipes, min($OFbeamsNeeded, $OFtoolsNeeded) + (18 - $OFpipesNeeded));
        if (floor($x / 9)) {
            $reward = 180 * floor($x / 9);
            if ($oilfield) {
                $tasks[] = taskText(floor(($x) / 9), 'steel pipes', 'iron works', 'oil field');
                $pipes -= $x;
                $cartsFlat += floor(($x) / 9);
            } else {
                $Btasks['oilfield'][] = array('Build a track to oil field. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(3, (20 - $OFbeamsNeeded)) <= $beams) {
        $x = min($beams, min($OFpipesNeeded, $OFtoolsNeeded) + (20 - $OFbeamsNeeded));
        if (floor($x / 3)) {
            $reward = 36 * floor($x / 3);
            if ($oilfield) {
                $tasks[] = taskText(floor(($x) / 3), 'beams', 'sawmill', 'oil field');
                $beams -= $x;
                $cartsStakes += floor(($x) / 3);
            } else {
                $Btasks['oilfield'][] = array('Build a track to oil field. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(32, (100 - $OFtoolsNeeded)) <= $tools) {
        $x = min($tools, min($OFbeamsNeeded, $OFpipesNeeded) + (100 - $OFtoolsNeeded));
        if (floor($x / 32)) {
            $reward = 640 * floor($x / 32);
            if ($oilfield) {
                $tasks[] = taskText(floor(($x) / 32), 'tools', 'iron works', 'oil field');
                $tools -= $x;
                $cartsBox += floor(($x) / 32);
            } else {
                $Btasks['oilfield'][] = array('Build a track to oil field. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(10, (100 - min(100, $IWcoalNeeded))) <= $coal) {
        $x = min($IWironNeeded + (100 - min(100, $IWcoalNeeded)), $coal);
        if (floor($x / 10)) {
            $reward = 150 * floor($x / 10);
            if ($ironworks) {
                $tasks[] = taskText(floor(($x) / 10), 'coal', 'coal mine', 'iron works');
                $cartsHopper += floor(($x) / 10);
            } else {
                $Btasks['ironworks'][] = array('Build a track to iron works. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(10, (100 - $IWironNeeded)) <= $iron) {
        $x = min($iron, $IWcoalNeeded + (100 - $IWironNeeded));
        if (floor($x / 10)) {
            $reward = 54 * floor($x / 10);
            if ($ironworks) {
                $tasks[] = taskText(floor(($x) / 10), 'raw iron', 'smelter', 'iron works');
                $cartsStakes += floor(($x) / 10);
            } else {
                $Btasks['ironworks'][] = array('Build a track to iron works. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(8, (100 - $SMcordwoodNeeded)) >= 8) {
        $x = floor(min(8, (100 - $SMcordwoodNeeded)) / 8);
        if ($x) {
            $reward = 80 * $x;
            if ($smelter) {
                $tasks[] = taskText($x, 'cordwood', 'logging camp', 'smelter');
                $cartsCordwood += $x;
            } else {
                $Btasks['smelter'][] = array('Build a track to smelter. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(10, (100 - min(100, $SMironOreNeeded))) <= $ironOre) {
        $x = min($ironOre, $SMcordwoodNeeded + (100 - min(100, $SMironOreNeeded)));
        if (floor($x / 10)) {
            $reward = 140 * floor($x / 10);
            if ($smelter) {
                $tasks[] = taskText(floor(($x) / 10), 'iron ore', 'iron mine', 'smelter');
                $cartsHopper += floor(($x) / 10);
            } else {
                $Btasks['smelter'][] = array('Build a track to smelter. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(3, (20 - $IMbeamsNeeded)) <= $beams) {
        $x = min($beams, $IMlumberNeeded + (20 - $IMbeamsNeeded));
        if (floor($x / 3)) {
            $reward = 36 * floor($x / 3);
            if ($ironmine) {
                $tasks[] = taskText(floor(($x) / 3), 'beams', 'sawmill', 'iron mine');
                $beams -= $x;
                $cartsStakes += floor(($x) / 3);
            } else {
                $Btasks['ironmine'][] = array('Build a track to iron mine. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(6, (24 - $IMlumberNeeded)) <= $lumber) {
        $x = min($lumber, $IMbeamsNeeded + (24 - $IMlumberNeeded));
        if (floor($x / 6)) {
            $reward = 72 * floor($x / 6);
            if ($ironmine) {
                $tasks[] = taskText(floor(($x) / 6), 'lumber', 'sawmill', 'iron mine');
                $lumber -= $x;
                $cartsStakes += floor(($x) / 6);
            } else {
                $Btasks['ironmine'][] = array('Build a track to iron mine. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(3, (20 - $CMbeamsNeeded)) <= $beams) {
        $x = min($beams, $CMrailsNeeded + (20 - $CMbeamsNeeded));
        if (floor($x / 3)) {
            $reward = 36 * floor($x / 3);
            if ($coalmine) {
                $tasks[] = taskText(floor(($x) / 3), 'beams', 'sawmill', 'coal mine');
                $beams -= $x;
                $cartsStakes += floor(($x) / 3);
            } else {
                $Btasks['coalmine'][] = array('Build a track to coal mine. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if (min(10, (50 - $CMrailsNeeded)) <= $rails) {
        $x = min($rails, $CMbeamsNeeded + (50 - $CMrailsNeeded));
        if (floor($x / 10)) {
            $reward = 180 * floor($x / 10);
            if ($coalmine) {
                $tasks[] = taskText(floor(($x) / 10), 'rails', 'smelter', 'coal mine');
                $cartsStakes += floor(($x) / 10);
            } else {
                $Btasks['coalmine'][] = array('Build a track to coal mine. (potential $' . $reward . ')', $reward);
            }
        }
    }
    if ($SMlogsNeeded < 100) {
        $x = 100 - $SMlogsNeeded;
        if (floor($x / 6)) {
            $reward = 60 * floor($x / 6);
            if ($sawmill) {
                $tasks[] = taskText(floor(($x) / 6), 'logs', 'logging camp', 'sawmill');
                $cartsFlat += floor(($x) / 6);
            } else {
                $Btasks[]['sawmill'][] = array('Build a track to sawmill. (potential $' . $reward . ')', $reward);
            }
        }
    }
    $fdTasks = array();
    foreach ($fireDepots as $index => $need) {
        $near = $need[1];
        $need = $need[0];
        if ((32 - $need) >= 8) {
            $x = floor((32 - $need) / 8);
            if ($x) {
                $fdTasks[$index] = $x;
                $fdNear[$index] = $near;
            }
        }
    }
    arsort($fdTasks);
    foreach ($fdTasks as $index => $x) {
        $tasks[] = taskText($x, 'cordwood', 'logging camp', 'firewood depot #' . ($index + 9));
    }

    foreach ($industryData['Frames'] as $i => $frame) {
        if ($frame['Type'] == 'flatcar_cordwood') {
            $cartsCordwood--;
        }
        if ($frame['Type'] == 'flatcar_logs') {
            $cartsFlat--;
        }
        if ($frame['Type'] == 'flatcar_stakes') {
            $cartsStakes--;
        }
        if ($frame['Type'] == 'flatcar_tanker') {
            $cartsTanker--;
        }
        if ($frame['Type'] == 'boxcar') {
            $cartsBox--;
        }
        if ($frame['Type'] == 'flatcar_hopper') {
            $cartsHopper--;
        }
        if (isset($cartTracks[$i]) && $cartTracks[$i]['d'] > 425) {
            if (!isInShed($frame, $industryData['Industries'], $ah)) {
                $Rtasks[] = array(
                    'Recover rolling stock ' . $frame['Type'] . ' ' . strip_tags($frame['Name'] . ' ' . $frame['Number']) . '. It is ' .
                    ceil($cartTracks[$i]['d'] / 100) . 'm off regular track near ' .
                    $ah->nearestIndustry($frame['Location'], $industryData['Industries']),
                    array(
                        'x' => $frame['Location'][0],
                        'y' => $frame['Location'][1],
                    )
                );
            }
        }
    }
    if ($cartsTanker > 0) {
        $Stasks[] = array('Buy ' . $cartsTanker . ' new tanker cart' . plural($cartsTanker) . ' for oil (-$' . (1450 * $cartsTanker) . ')', 1450 * $cartsTanker);
    }
    if ($cartsBox > 0) {
        $Stasks[] = array('Buy ' . $cartsBox . ' new box cart' . plural($cartsBox) . ' for tools (-$' . (950 * $cartsBox) . ')', 950 * $cartsBox);
    }
    if ($cartsStakes > 0) {
        $Stasks[] = array('Buy ' . $cartsStakes . ' new stake cart' . plural($cartsStakes) . ' for beams/lumber/rail/steel (-$' . (250 * $cartsStakes) . ')', 250 * $cartsStakes);
    }
    if ($cartsFlat > 0) {
        $Stasks[] = array('Buy ' . $cartsFlat . ' new flat cart' . plural($cartsFlat) . ' for logs/pipes (-$' . (300 * $cartsFlat) . ')', 300 * $cartsFlat);
    }
    if ($cartsCordwood > 0) {
        $Stasks[] = array('Buy ' . $cartsCordwood . ' new cordwood cart' . plural($cartsCordwood) . ' for cordwood/barrels (-$' . (275 * $cartsCordwood) . ')', 275 * $cartsCordwood);
    }
    if ($cartsHopper > 0) {
        $Stasks[] = array('Buy ' . $cartsHopper . ' new hopper' . plural($cartsHopper) . ' for ironore/coal (-$' . (950 * $cartsHopper) . ')', 950 * $cartsHopper);
    }


    return array($tasks, $Btasks, $Stasks, $Rtasks);
}

function insertLink($text)
{
    global $coords;
    if (isset($coords[$text])) {
        $link = '<span style="text-decoration:underline" onclick="zoomTo(' .
            (((400000 - ($coords[$text][0] + 200000)) / 400000)) . ', ' .
            (((400000 - ($coords[$text][1] + 200000)) / 400000)) .
            ')">' . $text . '</span>';
        return $link;
    } else {
        return $text;
    }

}

function taskText($carts, $cargo, $from, $to)
{
    $rewardMultiplier = 0;
    switch ($cargo) {
        case 'logs' :
            $rewardMultiplier = 60;
            break;
        case 'steel pipes' :
            $rewardMultiplier = 180;
            break;
        case 'lumber' :
            $rewardMultiplier = 72;
            break;
        case 'raw iron' :
            $rewardMultiplier = 54;
            break;
        case 'rails' :
            $rewardMultiplier = 180;
            break;
        case 'beams' :
            $rewardMultiplier = 36;
            break;
        case 'cordwood' :
            $rewardMultiplier = 80;
            break;
        case 'oil barrels' :
            $rewardMultiplier = 1840;
            break;
        case 'coal' :
            $rewardMultiplier = 150;
            break;
        case 'iron ore' :
            $rewardMultiplier = 140;
            break;
        case 'crude oil' :
            $rewardMultiplier = 192;
            break;
        case 'tools' :
            $rewardMultiplier = 480;
            break;
    }

    return array('Deliver ' . $carts . ' cart' . plural($carts) . ' of ' . $cargo . ' from ' .
        insertLink($from) . ' to ' . insertLink($to) . '. ($' . ($carts * $rewardMultiplier) . ')', $carts * $rewardMultiplier);
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


function isInShed($vehicle, $industries, ArithmeticHelper $ah)
{
    $shedLength = 2500;
    $shedWidth = 800;
    foreach ($industries as $industry) {
        if (in_array($industry['Type'], [11, 12, 13, 14])) {
            $exitPointX = $industry['Location'][0] + cos(deg2rad($industry['Rotation'][1])) * $shedLength;
            $exitPointY = $industry['Location'][1] + sin(deg2rad($industry['Rotation'][1])) * $shedLength;
        }
        if (
            $ah->dist($industry['Location'], $vehicle['Location'], true) +
            $ah->dist([$exitPointX, $exitPointY], $vehicle['Location'], true)
            < $shedLength + ($shedWidth / 2)) {
            return true;
        }
    }
    return false;
}

function query($sql)
{
    global $dbh;
    if (!$dbh) {
        connect();
    }

    $result = array();
    $rh = mysqli_query($dbh, $sql);
    if (!$rh) {
//        var_dump($sql);
    }
    while ($result[] = @mysqli_fetch_assoc($rh)) ;
    array_pop($result);

    return $result;
}

function connect()
{
    global $dbh;
    if (file_exists('../dbaccess.php'))
        require '../dbaccess.php';
    if (file_exists('dbaccess.php'))
        require 'dbaccess.php';

    $dbh = mysqli_connect($dbhost, $dbuser, $dbpassword);
    mysqli_query($dbh, 'use ' . $dbdatabase);

}