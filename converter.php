<?php
error_reporting(E_ALL);
ini_set('memory_limit', -1);  // just in case - previous versions had 8000x8000 px truecolor images JPEG
set_time_limit(90);                // just in case something wents really bad -- kill script after 10 seconds

require_once 'utils/dtAbstractData.php';
require_once 'utils/dtDynamic.php';
require_once 'utils/dtHeader.php';
require_once 'utils/dtProperty.php';
require_once 'utils/dtString.php';
require_once 'utils/dtVector.php';
require_once 'utils/dtArray.php';
require_once 'utils/dtStruct.php';
require_once 'utils/dtTextProperty.php';
require_once 'utils/GVASParser.php';
require_once 'utils/SaveReader.php';

if (!isset($_POST['metalOverWood'])) {
    $_POST['metalOverWood'] = 'NO';
}

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

//$_POST['save'] = 'Joetraincool.sav';
//$_POST['replant'] = 'NO';
//$_POST['firstTree'] = 'A';
//$_POST['userTree'] = 'A';
//$_POST['name_2'] = '2605';
//$_POST['name_0'] = '2605';
//$_POST['xp_0'] = 10;
//$_POST['money_0'] = 10;
//$_POST['underground_0'] = 10;
//$_POST['deletePlayer_0'] = '1';
//$_POST['product0_0'] = '1';
//$_POST['allBrakes'] = 'YES';
//$_POST['renameWhat'] = 'locos';
//$_POST['nameAllCountries'] = 'dnrgLocomotives';
//$_POST['cargoType_2'] = 'log';

if (!isset($NEWUPLOADEDFILE)) {
    if (isset($_POST['save']) && $_POST['save']) {
        $NEWUPLOADEDFILE = str_replace('./', '', $_POST['save']);
    } else {
        echo "! No file for conversion ! \r\n\n";
        die();
    }
}

$files = array($NEWUPLOADEDFILE);

foreach ($files as $file) {
    if (!file_exists($file)) {
        if (headers_sent()) {
            echo "";
            continue;
        } else {
            header('Location: /');
            die();
        }
    }

    $fileName = $file;

    $slotExtension = explode('-', substr($fileName,0,-4));
    $slotExtension='-'.$slotExtension[sizeof($slotExtension)-1];

    $myParser = new GVASParser();
    $newSaveFileContents = $myParser->parseData(file_get_contents($fileName), true, $slotExtension);
    unset($myParser);

    $file = fopen($fileName, 'wb');
    if ($file === false) {
        die('Unable to write file.');
    }
    fwrite($file, $newSaveFileContents);
    fclose($file);

    $myParser = new GVASParser();
    $myParser->parseData(file_get_contents($fileName), false, $slotExtension);
    $saveReadr = new SaveReader($myParser->goldenBucket);
    $saveReadr->addDatabaseEntry($fileName, isset($_POST['public']));
    header('Location: /map.php?name=' . str_replace('.sav', '', basename($fileName)));
}
