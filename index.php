<!DOCTYPE html>
<html lang="en">
<?php
    $PageTitle="RailroadsOnlineMapper";
    include_once('includes/head.php');
?>
<body>
    <header class="header">
        <h1 class="logo">RailroadsOnlineMapper</h1>
        <a class="button" href="upload.php">Upload Savegame</a>
    </header>
    <main>
        <section class="uploads">
            <h2>Latest uploads</h2>
            <div class="uploads__tables">
                <table>
                    <thead>
                        <th>NAME</th><th>Track Length</th><th># Y</th><th>Locos</th><th>Carts</th><th>max Slope</th>
                    </thead>
                    <?php
                    $dh = opendir('done/');
                    while ($file = readdir($dh)) {
                        if (substr($file, -5) == '.html') {
                            $files[filemtime('done/' . $file)] = 'done/' . $file;
                        }
                    }
                    @$db = unserialize(@file_get_contents('db.db'));
                    //array($totalTrackLength, $totalSwitches, $totalLocos, $totalCarts, $maxSlope);
                    krsort($files);

                    $hard_limit = 400;
                    $soft_limit = 140;
                    for ($i = 0; $i < sizeof($files); $i++) {
                        $file = array_shift($files);
                        if (!$file) break;

                        if ($i > $hard_limit) {
                            unlink("done/'.substr($file,5,-5).'.html");
                        }

                        if ($i >= $soft_limit) {
                            continue;
                        }


                        echo '<tr><td><a href="done/' . substr($file, 5, -5) . '.html?t=' . time() . '">' . substr($file, 5, -5) . '</a></td>
                                <td>' . round($db[substr($file, 5, -5) . '.sav'][0] / 100000, 2) . 'km</td>
                                <td>' . $db[substr($file, 5, -5) . '.sav'][1] . '</td>
                                <td>' . $db[substr($file, 5, -5) . '.sav'][2] . '</td>
                                <td>' . $db[substr($file, 5, -5) . '.sav'][3] . '</td>
                                <td >' . round($db[substr($file, 5, -5) . '.sav'][4]) . '%</td>
                                </tr>';
                        if (!(($i + 1) % 35)) {
                            echo '</table>';
                            if (($i + 1) < $soft_limit) {
                                echo $tableHeader;
                            }
                        }

                    }
                    ?>
                </table>
            </div>
        </section>
    </main>
    <?php include_once('includes/footer.php') ?>
</body>
</html>
<?php
$dir = 'saves';
$dh = opendir($dir);

while($file=readdir($dh)){
    if($file && (substr($file,-4) == '.sav' || substr($file,-13) == '.sav.modified')){
        if(filemtime($dir.'/'.$file)<time()-300){
            unlink($dir.'/'.$file);
        }
    }
}