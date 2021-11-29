<?php
$files=0;
$dh = opendir('saves');
while($file = readdir($dh)){
    if(substr($file,-4) == '.sav'){
        $files++;
        if(strpos(file_get_contents('saves/'.$file), 'SaveGameVersion')){
            echo $file."\n";
        }
    }
}
echo "found $files save games\n";
