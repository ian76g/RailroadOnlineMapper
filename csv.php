<?php
// server should keep session data for AT LEAST 1 hour
ini_set('session.gc_maxlifetime', 36000);
// each client should remember their session id for EXACTLY 1 hour
session_set_cookie_params(36000);
session_start();
//error_reporting(E_ALL);
require_once 'utils/ArithmeticHelper.php';
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
require_once 'utils/functions.php';
require_once 'utils/SaveReader.php';

while (!mkdir('lock')) {
}
file_put_contents('counter', $counter = file_get_contents('counter') + 1);
rmdir('lock');

//$_GET['name']='test';
$saveFile = null;
$json = null;
//$_GET['name']='ian76g-10';
if (isset($_GET['name']) && $_GET['name'] != '') {
    $saveFile = "./saves/" . $_GET['name'] . ".sav";
    if (file_exists($saveFile)) {
        $slotExtension = explode('-', substr($saveFile, 0, -4));
        $slotExtension = '-' . $slotExtension[sizeof($slotExtension) - 1];
        $ah = new ArithmeticHelper();
        $parser = new GVASParser();
        $parser->owner = $_GET['name'];
        $json = $parser->parseData(file_get_contents($saveFile), false, $slotExtension);
    } else {
        die('Map does not exist');
    }
}

echo '<pre>';
if(isset($_GET['exampleA'])){
    foreach($parser->goldenBucket['Frames'] as $frame){
        echo $frame['Type'];
        echo ";";
        echo trim(strip_tags($frame['Number']));
        echo ";";
        echo trim(strip_tags($frame['Name']));
        echo ";";
        echo $frame['Freight']['Type'];
        echo ";";
        echo $frame['Freight']['Amount'];
        echo ";";
        echo "\n";
    }
    die();
}
if(isset($_GET['type'])){
    echo json_encode($parser->goldenBucket[$_GET['type']], JSON_PRETTY_PRINT);
} else {
    echo json_encode($parser->goldenBucket, JSON_PRETTY_PRINT);
}
