<?php
ini_set('memory_limit', -1);  // just in case - previous versions had 8000x8000 px truecolor images JPEG
set_time_limit(10);                // just in case something wents really bad -- kill script after 10 seconds
$v = 46;                                  //version - totally not used except in next line
//echo "\n" . 'running converter version 0.' . $v . "\n";

require_once 'classes/ArithmeticHelper.php';
require_once 'classes/dtDynamic.php';
require_once 'classes/dtHeader.php';
require_once 'classes/dtProperty.php';
require_once 'classes/dtString.php';
require_once 'classes/GVASParser.php';
require_once 'classes/Mapper.php';

function getUserIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * define some stuff we need later
 */
$start = microtime(true);           // to caclulate runtime at end

$path = 'uploads';                         // set the path to find the save games.... last line wins

if (isset($argv[1]) && !empty($argv[1])) {
    $path = pathinfo($argv[1])['dirname']; // run from commandline grabbing path from parameter
}

$empty = false;
if (isset($_POST['empty']) && $_POST['empty']) {
    $empty = true;                          // render table of rolling stock without names?
}

$bg = 'bg5';                                // choose a background image
if (isset($_POST['background'])) {
    if ($_POST['background'] == 'bg') {
        $bg = 'bg';
    }
    if ($_POST['background'] == 'bg3') {
        $bg = 'bg3';
    }
    if ($_POST['background'] == 'bg4') {
        $bg = 'bg4';
    }
    if ($_POST['background'] == 'bg5') {
        $bg = 'bg5';
    }
}

// each different background image needs different croping and stretching params
$bgOffsets = array(
    'bg5' => array(9400, 9600, -720, -770, 9400, 9600),
    'bg4' => array(9400, 9600, -720, -770, 9400, 9600),
    'bg3' => array(8000, 8000, 0, 50, 8000, 8000),
    'bg' => array(8000, 8000, 0, 0, 8000, 8000),
);

// devine the SVG structure of the output-map
$htmlSvg = file_get_contents('map.template.html');
$pattern = '
<pattern id="bild" x="0" y="0" width="' . $bgOffsets[$bg][0] . '" height="' . $bgOffsets[$bg][1] . '" patternUnits="userSpaceOnUse">
                <image x="' .
    ((isset($_POST['xoff']) && $_POST['xoff'] !== '') ? ($_POST['xoff']) : $bgOffsets[$bg][2]) . '" y="' .
    ((isset($_POST['yoff']) && $_POST['xoff'] !== '') ? ($_POST['yoff']) : $bgOffsets[$bg][3]) . '" width="' .
    ((isset($_POST['xsoff']) && $_POST['xoff'] !== '') ? ($_POST['xsoff']) : $bgOffsets[$bg][4]) . '" height="' .
    ((isset($_POST['ysoff']) && $_POST['xoff'] !== '') ? ($_POST['ysoff']) : $bgOffsets[$bg][5]) . '" href="' . $bg . '.png" />
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
            $path = 'saves';
            $NEWUPLOADEDFILE = $_POST['save'];
        } else {
            die('no file submitted' . "\n");            // you either call it from commandline, upload or edit.
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
        header('Location: https://minizwerg.online/');
        die();
    }
    $htmlFileName = str_replace('.sav', '', basename($file)) . '.html';
    $downloadLink = '';
    if (file_exists('public/' . basename($file))) {
        $downloadLink = '<A href="../public/' . basename($file) . '">Download Save</A>';
    }
    $htmlSvg = str_replace('###DOWNLOAD###', $downloadLink, $htmlSvg);


    $svg = '';
    $savegame = $path . "/" . $file;

    $myParser = new GVASParser();                       // grab a Parser
    $myParser->NEWUPLOADEDFILE = $NEWUPLOADEDFILE;      // give the parser a filename

    $data = $myParser->parseData(file_get_contents($path . '/' . $file));
    if ($data == 'AGAIN') {
        // hack - we read a small struct - and inject new structure elements (empty cart numbers)
        // therefore we need to parse the new struct again.
        $data = $myParser->parseData(file_get_contents($path . '/' . $file), false);
    }
    $data = json_decode($data, true);

    $myMapper = new Mapper($data);
    $svg = $myMapper->gethtmlSVG($htmlSvg, $NEWUPLOADEDFILE, $empty, $arithmeticHelper);

    //@print_r($distances);
    file_put_contents('done/' . $htmlFileName, str_replace('&nbsp;', ' ', str_replace('###SVG###', $svg, $htmlSvg)));

    // optionally pushing this automatically to a webserver
    // if you run this script as deamon - it uploads on each save automatically
    $cmd = '"c:\Program Files (x86)\WinSCP\WinSCP.com" /command "open ftp://user:password@server.de/" "put ' . $htmlFileName . ' /html/minizwerg/" "exit"';
//        passthru($cmd);

    //echo "rendered in " . (microtime(true) - $start) . " microseconds\n";

    // store save for 5 minutes in case someone wants to edit the cart texts/numbers
    if (!isset($_POST['save'])) {
        rename('uploads/' . $NEWUPLOADEDFILE, 'saves/' . $NEWUPLOADEDFILE);
    }
}