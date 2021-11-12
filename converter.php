<?php

error_reporting(E_ALL);
ini_set('memory_limit', -1);  // just in case - previous versions had 8000x8000 px truecolor images JPEG
set_time_limit(90);                // just in case something wents really bad -- kill script after 10 seconds
$v = 46;                                  //version - totally not used except in next line

require_once 'classes/ArithmeticHelper.php';
require_once 'classes/dtAbstractData.php';
require_once 'classes/dtDynamic.php';
require_once 'classes/dtHeader.php';
require_once 'classes/dtProperty.php';
require_once 'classes/dtString.php';
require_once 'classes/dtVector.php';
require_once 'classes/dtArray.php';
require_once 'classes/dtStruct.php';
require_once 'classes/dtTextProperty.php';
require_once 'classes/GVASParser.php';
require_once 'classes/Mapper.php';

function getUserIpAddr()
{
    global $argv;

    if(isset($argv) && $argv){
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

//$_POST['save'] = 'slot1.sav';
//$_POST['replant'] = 'NO';
//$_POST['firstTree'] = 'A';
//$_POST['userTree'] = 'A';
//$_POST['name_2'] = '2605';
//$_POST['name_0'] = '2605';
//$_POST['xp_0'] = 10;
//$_POST['money_0'] = 10;
//$_POST['underground_0'] = 10;

// Define some stuff we need later

// to caclulate runtime at end
$start = microtime(true);

// set the path to find the save games.... last line wins
$path = 'uploads/';

if (isset($argv[1]) && !empty($argv[1])) {
    // run from commandline grabbing path from parameter
    $path = pathinfo($argv[1])['dirname'];
}

$empty = false;

if (isset($_POST['empty']) && $_POST['empty']) {
    // render table of rolling stock without names?
    $empty = true;
}

// choose a background image
$bg = 'bg5.jpg';
if (isset($_POST['background'])) {
    if ($_POST['background'] == 'bg') {
        $bg = 'bg.png';
    }
    if ($_POST['background'] == 'bg3') {
        $bg = 'bg3.png';
    }
    if ($_POST['background'] == 'bg4') {
        $bg = 'bg4.png';
    }
    if ($_POST['background'] == 'bg5') {
        $bg = 'bg5.jpg';
    }
}

// each different background image needs different croping and stretching params

$bgOffsets = array(
    'bg5.jpg' => array(8000, 8000, 0, 0, 8000, 8000),
    'bg4.png' => array(8000, 8000, 0, 0, 8000, 8000),
    'bg3.png' => array(8000, 8000, 0, 0, 8000, 8000),
    'bg.png' => array(8000, 8000, 0, 0, 8000, 8000),
);

// devine the SVG structure of the output-map
$htmlSvg = file_get_contents('map.template.html');
$pattern = '
<pattern id="bild" x="0" y="0" width="' . $bgOffsets[$bg][0] . '" height="' . $bgOffsets[$bg][1] . '" patternUnits="userSpaceOnUse">
                <image x="' .
    ((isset($_POST['xoff']) && $_POST['xoff'] !== '') ? ($_POST['xoff']) : $bgOffsets[$bg][2]) . '" y="' .
    ((isset($_POST['yoff']) && $_POST['xoff'] !== '') ? ($_POST['yoff']) : $bgOffsets[$bg][3]) . '" width="' .
    ((isset($_POST['xsoff']) && $_POST['xoff'] !== '') ? ($_POST['xsoff']) : $bgOffsets[$bg][4]) . '" height="' .
    ((isset($_POST['ysoff']) && $_POST['xoff'] !== '') ? ($_POST['ysoff']) : $bgOffsets[$bg][5]) . '" href="/topo/' . $bg . '" />
            </pattern>
';
$htmlSvg = str_replace('###PATTERN###', $pattern, $htmlSvg);


/**
 * read all save files
 */

$dh = opendir($path);

$files = array();

while ($file = readdir($dh)) {
    if (substr($file, -4) == '.sav') {
        $mtime = filemtime($path . '/' . $file);
        $files[$mtime] = $file;
    }
}

/**
 * find most recent save
 */

krsort($files);

if (!isset($NEWUPLOADEDFILE)) {                         // will be set in upload.php after upload.
    if (isset($argv[1])) {
        $NEWUPLOADEDFILE = $argv[1];                    // or we find it on command line
    } else {
        if (isset($_POST['save']) && $_POST['save']) {  // but this can also be aquired after editing the save out of the map.html
            $path = 'saves/';
            $NEWUPLOADEDFILE = $_POST['save'];
        } else {
            // Unable to find a file to convert
            echo "! No file for conversion ! \r\n\n";
            // you either call it from commandline, upload or edit.
            die();
        }
    }
}

$files = array($NEWUPLOADEDFILE);                       // override allFiles with just the one file specified

$arithmeticHelper = new ArithmeticHelper();             // put some math stuff in an extra class
/**
 * /*
 * do all files that need to be done  (was overriden by a single file)
 */

foreach ($files as $file) {
    if (!file_exists($path . '/' . $file)) {
        // Headers already sent? Don't need to resend.
        if (headers_sent()) {
          echo "";
          continue;
        } else {
          header('Location: /');
          die();
        }

    }

    $htmlFileName = str_replace('.sav', '', basename($file)) . '.html';

    $downloadLink = '';

    // Old method of retrieving save files set to public; Can remove once 100% no longer needed
    //
    // if (file_exists('public/' . basename($file))) {
    //     $downloadLink = '<a href="./public' . basename($file) . '">Download Save</A>';
    //     echo "Download Link: $downloadLink";
    // }

    $htmlSvg = str_replace('###DOWNLOAD###', $downloadLink, $htmlSvg);

    $svg = '';
    $savegame = $path . "/uploads/" . $file;

    $myParser = new GVASParser();                       // grab a Parser
    $myParser->NEWUPLOADEDFILE = $NEWUPLOADEDFILE;      // give the parser a filename
    $myParser->parseData(file_get_contents($path . '/' . $file), false);

    $myMapper = new Mapper($myParser->goldenBucket);

    $svg = $myMapper->gethtmlSVG($htmlSvg, $NEWUPLOADEDFILE, $empty, $arithmeticHelper);

    //@print_r($distances);
    file_put_contents('maps/' . $htmlFileName, str_replace('&nbsp;', ' ', str_replace('###SVG###', $svg, $htmlSvg)));

    // optionally pushing this automatically to a webserver
    // if you run this script as deamon - it uploads on each save automatically
    // $cmd = '"c:\Program Files (x86)\WinSCP\WinSCP.com" /command "open ftp://user:password@server.de/" "put ' . $htmlFileName . ' /html/minizwerg/" "exit"';
    //        passthru($cmd);

    //echo "rendered in " . (microtime(true) - $start) . " microseconds\n";

    // Moves saves to appropriate folders for use
    if(isset($_POST['public'])) {
      rename('uploads/' . $NEWUPLOADEDFILE, 'saves/public/' . $NEWUPLOADEDFILE);
    } else {
      rename('uploads/' . $NEWUPLOADEDFILE, 'saves/' . $NEWUPLOADEDFILE);
    }


}
