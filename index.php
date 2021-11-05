<?php
$PageTitle = "RailroadsOnlineMapper";
include_once('includes/head.php');
include_once 'utils/database.php';

$hard_limit = 400;
$soft_limit = 90;

# Delete the oldest files if hard_limit is reached
$dh = opendir('done/');
while ($file = readdir($dh)) {
    if (substr($file, -5) == '.html') {
        $files[filemtime('done/' . $file)] = 'done/' . $file;
    }
}
krsort($files);

for ($i = 0; $i < sizeof($files); $i++) {
    $file = array_shift($files);
    if (!$file) break;

    if ($i > $hard_limit) {
        unlink("done/'.substr($file,5,-5).'.html");
    }
}

# Delete public files after 5 minutes
$dir = 'saves';
$dh = opendir($dir);

while ($file = readdir($dh)) {
    if ($file && (substr($file, -4) == '.sav' || substr($file, -13) == '.sav.modified')) {
        if (filemtime($dir . '/' . $file) < time() - 300) {
            unlink($dir . '/' . $file);
        }
    }
}

$tableHeader = <<<EOL
<thead>
<th>NAME</th>
<th>Track Length</th>
<th># Y</th>
<th>Locos</th>
<th>Carts</th>
<th>max Slope</th>
</thead>
EOL;
?>
    <body>
<header class="header">
    <h1 class="logo"><?php print($PageTitle); ?></h1>
    <a class="button" href="upload.php">Upload Save file</a>
</header>
<main>
    <section class="uploads">
        <h2>Latest uploads</h2>
        <div class="uploads__tables">
            <table>
                <?php
                print($tableHeader);

                $data = getSaves();

                $i = 0;
                while ($save = $data->fetchArray(SQLITE3_ASSOC)) {
                    $html_file = "done/" . str_replace(".sav", ".html", $save['filename']);
                    $pub_file = "public/" . $save['filename'];
                    $save_name = str_replace(".sav", "", $save['filename']);
                    if (file_exists($pub_file)) {
                        $save_name .= " (DL)";
                    }

                    print("<tr>" . PHP_EOL);
                    print("<td>" . PHP_EOL);
                    print("<a href='" . $html_file . "?t=" . time() . "'>" . $save_name . "</a>" . PHP_EOL);
                    print("</td>" . PHP_EOL);
                    print("<td>" . PHP_EOL);
                    print(round(substr($save['track_length'] / 100000, 2) . "km") . PHP_EOL);
                    print("</td>" . PHP_EOL);
                    print("<td>" . PHP_EOL);
                    print($save['switch_count'] . PHP_EOL);
                    print("</td>" . PHP_EOL);
                    print("<td>" . PHP_EOL);
                    print($save['loc_count'] . PHP_EOL);
                    print("</td>" . PHP_EOL);
                    print("<td>" . PHP_EOL);
                    print($save['cart_count'] . PHP_EOL);
                    print("</td>" . PHP_EOL);
                    print("<td>" . PHP_EOL);
                    print(round($save['max_slope']) . "%" . PHP_EOL);
                    print("</td>" . PHP_EOL);
                    print("</tr>" . PHP_EOL);

                    $i += 1;
                    if (!(($i + 1) % 15)) {
                        if (($i + 1) < $soft_limit) {
                            echo '</table><table>' . $tableHeader;
                        }
                    }
                }
                ?>
            </table>
        </div>
    </section>
</main>
<?php include_once('includes/footer.php') ?>