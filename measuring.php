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

$parser = new GVASParser();
$parser->parseData(file_get_contents('C:\\Users\\Sebastian\\AppData\\Local\\arr\\Saved\\SaveGames\\slot10.sav'), false, 10);
$gb = $parser->goldenBucket;

$ah = new ArithmeticHelper();
echo $ah->dist($gb['Frames'][0]['Location'], $gb['Frames'][1]['Location'], false)- (1571.207282/2);
echo "\n";
echo $ah->dist($gb['Splines'][2]['Segments'][0]['LocationStart'],$gb['Splines'][2]['Segments'][0]['LocationEnd']);
echo "\n";
echo $ah->dist($gb['Splines'][3]['Segments'][0]['LocationStart'], $gb['Splines'][3]['Segments'][0]['LocationEnd'], true);
