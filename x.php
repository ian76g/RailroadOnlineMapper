<?php
error_reporting(E_ALL && !E_WARNING);
require_once 'utils/dtAbstractData.php';
require_once 'utils/dtDynamic.php';
require_once 'utils/dtHeader.php';
require_once 'utils/dtProperty.php';
require_once 'utils/dtString.php';
require_once 'utils/dtVector.php';
require_once 'utils/dtArray.php';
require_once 'utils/dtStruct.php';
require_once 'utils/dtTextProperty.php';
require_once 'utils/functions.php';
require_once 'utils/GVASParser.php';
require_once 'utils/SaveReader.php';
require_once 'utils/ArithmeticHelper.php';

$sql= 'select * from stats';
connect();
$ah = new ArithmeticHelper();
$dh = opendir('saves');
while($files[]=readdir($dh));
$p = new GVASParser();
foreach($files as $file){
    if(substr($file,-4) == '.sav'){
        $_GET['name'] = substr($file,0,-4);
        $result = query('select * from stats where name = "'.substr($file,0,-4).'"');
        if(!sizeof($result) || $result[0]['length']===''){
            echo $file."<br>\n";
            $data = $p->parseData(file_get_contents('saves/'.$file));
            $p->buildGraph();
            $tasks = generateTasks($p->goldenBucket, $ah,
                array(
                    $p->industryTracks,
                    $p->cartTracks
                )
            );
            $s = new SaveReader($p->goldenBucket);
            var_dump($s->addDatabaseEntry(substr($file,0,-4), false, $tasks, true));
            $s->addDatabaseEntry(substr($file,0,-4), false, $tasks);
            $result = query('select * from stats where name = "'.substr($file,0,-4).'"');
            //            die();
        }
    }
}
//foreach($data as $p => $x){
//    if(isset($x[7]) && $x[7]){
//        query('update stats set unused="1" where name = "'.mysqli_real_escape_string($dbh, $p).'"');
//    }
//}
