<?php
//error_reporting(E_ALL);
require_once 'utils/functions.php';

connect();

$sql = 'SELECT *, SQRT((x-@x)*(x-@x)+(y-@y)*(y-@y)) as dist FROM `trees` WHERE x-1200<@x and x+1200>@x and y-1200<@y and y+1200>@y order by dist asc limit 1';

$sql = str_replace(
    array('@x', '@y'),
    array((int)$_GET['x'], (int)$_GET['y']), $sql
);
$result = query($sql);

echo $result[0]['z'];