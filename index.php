<?php
require_once 'config.php';
?>
    <!DOCTYPE html>
    <html lang="en">
    <?php
    $PageTitle = "RailroadsOnlineMapper";
    include_once(SHELL_ROOT . 'includes/head.php');

    // Create required folders if they don't exist
    $folders = array("public", "saves", "uploads");
    foreach ($folders as $folder) {
        if (!file_exists($folder)) {
            mkdir($folder);
        }
    }

    // Create counter if it doesn't exist
    if (!file_exists('counter')) {
        file_put_contents('counter', 0);
    }

    $tableHeader = '<thead>
                        <th>Player</th>
                        <th><A href="?sortby=0&sortorder=desc" style="color: white">Track Length</A></th>
                        <th><A href="?sortby=1&sortorder=desc" style="color: white">Switches</A></th>
                        <th><A href="?sortby=6&sortorder=desc" style="color: white">Trees Removed</A></th>
                        <th><A href="?sortby=2&sortorder=desc" style="color: white">Locos</A></th>
                        <th><A href="?sortby=3&sortorder=desc" style="color: white">Carts</A></th>
                        <th><A href="?sortby=4&sortorder=desc" style="color: white">Slope</A></th>
                        <th><A href="?sortby=4&sortorder=desc" style="color: white">Shared (2 Days)</th>
                    </thead>';

    function mysort($a, $b)
    {
        global $db;
        $x = 1;
        if (strtolower($db[substr($a, 5, -5) . '.sav'][$_GET['sortby']]) == strtolower($db[substr($b, 5, -5) . '.sav'][$_GET['sortby']])) {
            return 0;
        }
        if (strtolower($db[substr($a, 5, -5) . '.sav'][$_GET['sortby']]) > strtolower($db[substr($b, 5, -5) . '.sav'][$_GET['sortby']])) {
            $x = -1;
        } else {
            $x = 1;
        }
        if ($_GET['sortorder'] == 'desc') {
            return $x;
        } else {
            return -$x;
        }
    }


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
                    $dh = opendir(SHELL_ROOT . 'maps/');
                    while ($file = readdir($dh)) {
                        if (substr($file, -5) == '.html') {
                            $files[filemtime(SHELL_ROOT . 'maps/' . $file)] = SHELL_ROOT . 'maps/' . $file;
                        }
                    }
                    if ((isset($files) && $files != null) && file_exists('db.db')) {
                        $db = unserialize(file_get_contents('db.db'));
                        //array($totalTrackLength, $totalSwitches, $totalLocos, $totalCarts, $maxSlope);

                        if (!isset($_GET['sortby']) || !isset($_GET['sortorder'])) {
                            krsort($files);
                        } else {
                            usort($files, 'mysort');
                        }

                        $hard_limit = 1600;
                        $soft_limit = 800;
                        for ($i = 0; $i < sizeof($files); $i++) {
                            $file = array_shift($files);
                            if (!$file) break;

                            if ($i > $hard_limit) {
                                unlink(SHELL_ROOT . "maps/" . substr($file, 5, -5) . ".html");
                            }

                            if ($i >= $soft_limit) {
                                continue;
                            }

                            $dl = '';
                            if (file_exists(SHELL_ROOT . 'maps/' . substr($file, 5, -5) . '.sav')) {
                                $dl = ' (DL)';
                            }

                            echo
                                '<tr>
                                <!-- Owner of Map -->
                                <td><a href=" maps/' . substr($file, 5, -5) . '.html">' . substr($file, 5, -5) . '</a></td>

                                <!-- Track Length -->
                                <td>' . round($db[substr($file, 5, -5) . '.sav'][0] / 100000, 2) . 'km</td>

                                <!-- Switch Count -->
                                <td>' . $db[substr($file, 5, -5) . '.sav'][1] . '</td>

                                <!-- Tree Death Count -->
                                <td>' . $db[substr($file, 5, -5) . '.sav'][6] . '</td>

                                <!-- Locomotives Owned -->
                                <td>' . $db[substr($file, 5, -5) . '.sav'][2] . '</td>

                                <!-- Rolling Stock Owned -->
                                <td>' . $db[substr($file, 5, -5) . '.sav'][3] . '</td>

                                <!-- Max Slope -->
                                <td>' . round($db[substr($file, 5, -5) . '.sav'][4]) . '%</td>
                                <!-- Shared Link -->';
                            ?>
                            <?php

                            // Checks public save folder to see if we can provide a link
                            $saveCheck = SHELL_ROOT . 'saves/public/' . substr($file, 5, -5) . '.sav';
                            if (file_exists($saveCheck)) {
                                $upTime = filemtime($saveCheck);
                                $timeCheck = time() - $upTime;
                                if ($timeCheck < 172800) {
                                    echo '<td><a href="' . WWW_ROOT . 'saves/public/' . substr($file, 5, -5) . '.sav">Link</a></td>';
                                } else {
                                    echo '<td>Expired</td>';
                                }
                            } else {
                                echo '<td></a></td>';
                            }

                            ?>

                            <?php
                            echo '</tr>';
                            if (!(($i + 1) % 15)) {
                                if (($i + 1) < $soft_limit) {
                                    echo '</table><table>' . $tableHeader;
                                }
                            }

                        }
                    }
                    echo '</table>';
                    ?>
                </table>
            </div>
        </section>
    </main>
    <?php include_once(SHELL_ROOT . 'includes/footer.php') ?>
    </body>
    </html>
<?php
$dir = SHELL_ROOT . 'saves';
$dh = opendir($dir);

while ($file = readdir($dh)) {
    if ($file && (substr($file, -4) == '.sav' || substr($file, -13) == '.sav')) {
        if (filemtime($dir . '/' . $file) < time() - 600) {
            unlink($dir . '/' . $file);
        }
    }
}
