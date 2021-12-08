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

function getMarqueeTrain()
{
    $carts = array('boxcar50.png','boxcar2_50.png','boxcar3_50.png', 'tanker50.png', 'flatcar_logs50.png', 'rawmaterials50.png', 'hopper50.png');
    shuffle($carts);
    $length = rand(8, 24);
    $out = '<marquee scrollamount="' . rand(3, 9) . '">';
    if(rand(0,1)){
        $out.= '<img src="assets/images/loco50.png" style="height:25px;display:inline">';

    } else {
        $out.= '<img src="assets/images/locoporter50.png" style="height:25px;display:inline">';
    }
    for ($i = 0; $i < $length; $i++) {
        if (!rand(0, 3)) shuffle($carts);
        $out .= '<img src="assets/images/' . $carts[0] . '" style="height:25px;display:inline;">';
    }
    if(rand(0,2)){
        $out .= '<img src="assets/images/caboose50.png" style="height:25px;display:inline">';

    }
    $out .= '</marquee>' . "\n";

    return $out;
}

?>
<body>
<header class="header">
    <h1 class="logo">RailroadsOnlineMapper</h1>
    <a class="button" id="uploadButton">Upload Savegame</a>
</header>
<main>
    <?php echo getMarqueeTrain(); ?>
    <section class="uploads">
        <h2>Latest uploads (* = as download available)</h2>
        <div class="uploads__tables">
            <table id="saveFiles">
                <thead>
                <tr>
                    <th>
                        <img height="28" width="40" src="/assets/images/player.svg" alt="Name">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/distance.svg" alt="Track length">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/switch.svg" alt="Number of switches">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/tree.svg" alt="Number of cut down trees">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/loco.svg" alt="Number of locomotives">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/cart.svg" alt="Number of carts">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/slope.svg" alt="Biggest slope">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/tasks.png" alt="Tasks">
                    </th>
                    <th>
                        <img height="28" width="40" src="/assets/images/reward.png" alt="Reward">
                    </th>
                </tr>
                </thead>
                <?php
                foreach (map_entries(($_GET['sortby'] ?? null), $_GET['sortorder']) as $entry) {
                    $asterix = '';
                    if ($entry['public']) {
                        $asterix = '*';
                    }
                    print('<tr>' . PHP_EOL);
                    print('<td>' . $asterix . '<a href="map.php?name=' . $entry['name'] . '">' . $entry['name'] . '</a></td>' . PHP_EOL);
                    print('<td data-content="'.$entry['trackLength'].'">' . $entry['trackLength'] . ' km</td>' . PHP_EOL);
                    print('<td>' . $entry['numY'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numT'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numLocs'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numCarts'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['slope'] . '%</td>' . PHP_EOL);
                    print('<td>' . $entry['tasks'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['reward'] . '</td>' . PHP_EOL);
                    print('</tr>' . PHP_EOL);
                }
                echo '</table>';
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
                    <input type="checkbox" name="public" id="public"/> YES - Others may download my save file
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

    const myTable = document.querySelector("#saveFiles");
    const dataTable = new simpleDatatables.DataTable(myTable, {
        perPage: 15
    });
</script>
</body>
</html>
