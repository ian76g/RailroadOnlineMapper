<?php
// server should keep session data for AT LEAST 100 hour
ini_set('session.gc_maxlifetime', 360000);
// each client should remember their session id for EXACTLY 100 hour
session_set_cookie_params(360000);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<?php
//error_reporting(E_ALL);
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
<body bgcolor="black">
<header class="header">
    <h1 class="logo">RailroadsOnlineMapper</h1>
    <?php
    if(isset($_SESSION['steam_personaname'])) {
        echo '<img src="'.$_SESSION['steam_avatarmedium'].'">';
        echo '<A href="utils/Steam/steamauth.php?logout">Logout</A>';
        echo '<a class="button" id="uploadButton">Upload Savegame</a>';
    } else {
        if(isset($_SESSION['steamid'])&&!isset($_SESSION['steam_personaname'])){
            echo '<A href="utils/Steam/steamauth.php?update">2nd) fetch your Steam username before you can upload in next step</A>';
        } else {
            echo '<A href="utils/Steam/steamauth.php?login">To upload your save (in step 3) - you have to login at the Steam site first</A>';
        }
    }
    ?>

</header>
<main>
    <table><tr>
            <td>
                <h4><A href="https://wiki.minizwerg.online/"><img width="30" src="assets/images/wiki.png">Wiki<br>closed due to player abuse</A></h4>
            </td>
            <td style="width:200px;">&nbsp;</td>
            <td>
                <h4><A href="https://tom-90.github.io/RROx/"><img width="30" src="assets/images/appIcon.ico">_tom()s Minimap-Tool</A></h4>
            </td>
            <td style="width:200px;">&nbsp;</td>
            <td>
                <h4><A href="http://www.sharidan.dk/railroads-online/rowb/"><img width="30" src="assets/images/sharidan.png">Sharidans World Backup Tool</A></h4>
            </td>
            <td style="width:200px;">&nbsp;</td>
            <td>
                <h4><A href="https://rail-road-not-found.vercel.app/"><img width="30" src="assets/images/al.png">Albundys 3D Map Tool</A></h4>
            </td>
        </tr></table>

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
                    <th>
                        <img height="28" width="40" src="/assets/images/downloads.png" alt="Downloads">
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
                    print('<td>' . $entry['downloads'] . '</td>' . PHP_EOL);
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
    if(btn !== null){
        btn.onclick = function () {
            modal.style.display = "block";
        }
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
