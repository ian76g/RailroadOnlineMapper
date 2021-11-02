<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF8">
    <title>Railroads Online Map</title>
    <script src="js-image-zoom.js"></script>
	<style>* {font-family: Verdana; font-size: 9pt;}</style>
</head>
<body>
	<div width="100%">
	<?php
		$dh=opendir('done/');
		while($file=readdir($dh)){
			if(substr($file, -5)=='.html'){
				$files[filemtime('done/'.$file)] = 'done/'.$file;
			}
		}
    @$db=unserialize(@file_get_contents('db.db'));
    //array($totalTrackLength, $totalSwitches, $totalLocos, $totalCarts, $maxSlope);
echo '<hr><a href="upload.php" target="_upload">Add your Savegame here</a><br>most recent maps are: <hr>';
    $tableHeader='<table>
<tr><th>NAME</th><th>Track Length</th><th># Y</th><th>Locos</th><th>Carts</th><th>max Slope</th></tr>';

    echo '';
		krsort($files);
		echo '<span style="float:left;padding-right:50px;">'.$tableHeader;
		for($i=0; $i<120; $i++){
			$file = array_shift($files);
			if(!$file) break;
			echo '<tr><td><A href="done/'.substr($file,5,-5).'.html">'.substr($file,5,-5).'</A></td>
<td align="right">'.round($db[substr($file,5,-5).'.sav'][0]/100000,2).'km</td>
<td align="right">'.$db[substr($file,5,-5).'.sav'][1].'</td>
<td align="right">'.$db[substr($file,5,-5).'.sav'][2].'</td>
<td align="right">'.$db[substr($file,5,-5).'.sav'][3].'</td>
<td align="right">'.round($db[substr($file,5,-5).'.sav'][4]).'%</td>
</tr>';
			if(!(($i+1)%30)){
			    echo '</table></span><span style="float:left;padding-right:50px;">'.$tableHeader;
            }

		}
			echo '</table></span>';

	?>
	</div><hr style="clear:both">
made by ian76g#6577 on discord - used by people <?php file_put_contents('counter', $counter = file_get_contents('counter')+1); echo $counter?> times
</body>
</html>
