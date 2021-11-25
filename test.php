<?php
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
require_once 'utils/SaveReader.php';

$ah = new ArithmeticHelper();
$parser = new GVASParser();

$x = json_decode($parser->parseData(file_get_contents('slot1.sav'), false, ''), true);
//1158-71  1158-25

$segment1 = $x['Splines'][901]['Segments'][1];
$segment2 = $x['Splines'][17]['Segments'][57];


//1158-71  1158-25

$ah->getCurveCoordsBetweenSegments(
    $segment1,
    $segment2
);
