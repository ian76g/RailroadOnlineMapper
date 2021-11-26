<!DOCTYPE html>
<html lang="en">
<?php
$PageTitle = "RailroadsOnlineMapper";
include_once('includes/head.php');
include_once('utils/functions.php');

// Create required folder if it don't exist
if (!file_exists('./saves/')) {
    mkdir('./saves/');
}

// Create counter if it doesn't exist
if (!file_exists('counter')) {
    file_put_contents('counter', 0);
}

$tableHeader = '<thead>
                        <th style="background-color: beige">                                                        <img height="28" width="40" src="/assets/images/player.svg"></th>
                        <th style="background-color: beige"><A href="?sortby=0&sortorder=desc" style="color: white"><img height="28" width="40" src="/assets/images/distance.svg"></A></th>
                        <th style="background-color: beige"><A href="?sortby=1&sortorder=desc" style="color: white"><img height="28" width="40" src="/assets/images/switch.svg"></A></th>
                        <th style="background-color: beige"><A href="?sortby=6&sortorder=desc" style="color: white"><img height="28" width="40" src="/assets/images/tree.svg"></A></th>
                        <th style="background-color: beige"><A href="?sortby=2&sortorder=desc" style="color: white"><img height="28" width="40" src="/assets/images/loco.svg"></A></th>
                        <th style="background-color: beige"><A href="?sortby=3&sortorder=desc" style="color: white"><img height="28" width="40" src="/assets/images/cart.svg"></A></th>
                        <th style="background-color: beige"><A href="?sortby=4&sortorder=desc" style="color: white"><img height="28" width="40" src="/assets/images/slope.svg"></A></th>
                    </thead>';
?>
<body>
<header class="header">
    <h1 class="logo">RailroadsOnlineMapper</h1>
    <a class="button" id="uploadButton">Upload Savegame</a>
</header>
<main>
    <section class="uploads">
        <h2>Latest uploads (* = as download available)</h2>
        <div class="uploads__tables">
            <table>
                <?php
                echo $tableHeader;
                $soft_limit = 800;

                $i = 0;
                foreach (map_entries((isset($_GET['sortby'])?$_GET['sortby']:null), $_GET['sortorder']) as $entry) {
                    $asterix = '';
                    if ($entry['public']) {
                        $asterix = '*';
                    }
                    print('<tr>' . PHP_EOL);
                    print('<td>'.$asterix.'<a href="map.php?name=' . $entry['name'] . '">' . $entry['name'] . '</a></td>' . PHP_EOL);
                    print('<td>' . $entry['trackLength'] . 'km</td>' . PHP_EOL);
                    print('<td>' . $entry['numY'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numT'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numLocs'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numCarts'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['slope'] . '%</td>' . PHP_EOL);
                    print('</tr>' . PHP_EOL);

                    if (!(($i + 1) % 15)) {
                        if(($i+1)==15){
                            echo '</table></div><div class="uploads__tables"><details><summary>show more</summary><table>' . $tableHeader;
                        } else {
                            if (($i + 1) < $soft_limit) {
                                echo '</table><table>' . $tableHeader;
                            }
                        }
                    }
                    $i++;
                }
                echo '</table></details>';
                ?>
            </table>
        </div>
    </section>

    <div id="uploadForm" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form action="upload.php" class="upload-form" method="post" enctype="multipart/form-data">
                <h1>Upload your savefile</h1>
                <p>Open explorer at <code>%localappdata%\arr\saved\savegames\</code></p>
                <br>

                <section>
                    <h3>Select savefile</h3>
                    <input type="file" name="fileToUpload" id="fileToUpload">
                </section>

                <section>
                    <h3>Make it public</h3>
                    <input type="checkbox" name="public" id="public" />
                </section>

                <input class="button" type="submit" value="Upload" name="submit">
            </form>
        </div>
    </div>
</main>
<?php include_once('includes/footer.php') ?>
<script type="text/javascript">
    const modal = document.getElementById("uploadForm");
    const btn = document.getElementById("uploadButton");
    const span = document.getElementsByClassName("close")[0];
    btn.onclick = function () {
        modal.style.display = "block";
    }
    span.onclick = function () {
        modal.style.display = "none";
    }
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
</script>
</body>
</html>
