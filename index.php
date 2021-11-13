<!DOCTYPE html>
<html lang="en">
<?php
$PageTitle = "RailroadsOnlineMapper";
include_once('includes/head.php');
include_once('utils/tools.php');

// Create required folder if it don't exist
if (!file_exists('./saves/')) {
    mkdir('./saves/');
}

// Create counter if it doesn't exist
if (!file_exists('counter')) {
    file_put_contents('counter', 0);
}

$tableHeader = '<thead>
                        <th>Player</th>
                        <th><A href="?sortby=0&sortorder=desc" style="color: white">Length</A></th>
                        <th><A href="?sortby=1&sortorder=desc" style="color: white">Switches</A></th>
                        <th><A href="?sortby=6&sortorder=desc" style="color: white">Trees</A></th>
                        <th><A href="?sortby=2&sortorder=desc" style="color: white">Locos</A></th>
                        <th><A href="?sortby=3&sortorder=desc" style="color: white">Carts</A></th>
                        <th><A href="?sortby=4&sortorder=desc" style="color: white">Slope</A></th>
                    </thead>';
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
                <?php
                echo $tableHeader;
                $soft_limit = 800;

                $i = 0;
                foreach (map_entries() as $entry) {
                    print('<tr>' . PHP_EOL);
                    print('<td><a href="map.php?name=' . $entry['name'] . '">' . $entry['name'] . '</a></td>' . PHP_EOL);
                    print('<td>' . $entry['trackLength'] . 'km</td>' . PHP_EOL);
                    print('<td>' . $entry['numY'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numT'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numLocs'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['numCarts'] . '</td>' . PHP_EOL);
                    print('<td>' . $entry['slope'] . '%</td>' . PHP_EOL);
                    print('</tr>' . PHP_EOL);

                    if (!(($i + 1) % 15)) {
                        if (($i + 1) < $soft_limit) {
                            echo '</table><table>' . $tableHeader;
                        }
                    }
                    $i++;
                }
                echo '</table>';
                ?>
            </table>
        </div>
    </section>
</main>
<?php include_once('includes/footer.php') ?>
</body>
</html>
