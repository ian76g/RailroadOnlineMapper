<?php
require_once 'ArithmeticHelper.php';

$ah = new ArithmeticHelper();

//print_r($argv);
if(isset($argv[1])){
    $_GET['x1']=$argv[1];
}
if(isset($argv[2])){
    $_GET['y1']=$argv[2];
}
if(isset($argv[3])){
    $_GET['x2']=$argv[3];
}
if(isset($argv[4])){
    $_GET['y2']=$argv[4];
}
if(isset($argv[5])){
    $_GET['x3']=$argv[5];
}
if(isset($argv[6])){
    $_GET['y3']=$argv[6];
}
if(isset($argv[7])){
    $_GET['x4']=$argv[7];
}
if(isset($argv[8])){
    $_GET['y4']=$argv[8];
}

$ah->getCurveCoordsBetweenSegments(
    array(
        0=>array('X'=>$_GET['x1'], 'Y'=>$_GET['y1']),
        1=>array('X'=>$_GET['x2'], 'Y'=>$_GET['y2']),
    ),
    array(
        0=>array('X'=>$_GET['x3'], 'Y'=>$_GET['y3']),
        1=>array('X'=>$_GET['x4'], 'Y'=>$_GET['y4']),
    ));
