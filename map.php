<?php
//error_reporting(E_ALL);
require_once 'utils/ArithmeticHelper.php';
require_once 'utils/dtAbstractData.php';
require_once 'utils/dtDynamic.php';
require_once 'utils/dtHeader.php';
require_once 'utils/dtProperty.php';
require_once 'utils/dtString.php';
require_once 'utils/dtVector.php';
require_once 'utils/dtArray.php';
require_once 'utils/dtStruct.php';
require_once 'utils/dtTextProperty.php';
require_once 'utils/GVASParser.php';
require_once 'utils/functions.php';
require_once 'utils/SaveReader.php';

while (!mkdir('lock')) {
}
file_put_contents('counter', $counter = file_get_contents('counter') + 1);
rmdir('lock');

//$_GET['name']='test';
$saveFile = null;
$json = null;

if (isset($_GET['name']) && $_GET['name'] != '') {
    $saveFile = "./saves/" . $_GET['name'] . ".sav";
    if (file_exists($saveFile)) {
        $slotExtension = explode('-', substr($saveFile, 0, -4));
        $slotExtension = '-' . $slotExtension[sizeof($slotExtension) - 1];
        $ah = new ArithmeticHelper();
        $parser = new GVASParser();
        $json = $parser->parseData(file_get_contents($saveFile), false, $slotExtension);
        $parser->buildGraph();
        $tasks = generateTasks($parser->goldenBucket, $ah,
            array(
                $parser->industryTracks,
                $parser->cartTracks
            )
        );
        $sh = new SaveReader($parser->goldenBucket);
        $sh->updateDatabaseEntry($_GET['name'], $tasks);
    } else {
        die('Map does not exist');
    }
}

$dataArray = json_decode($json, true);

$dh = opendir('includes');
$textFiles = array();
while ($textFiles[] = readdir($dh)) ;
$textOptions = '';
foreach ($textFiles as $textFile) {
    if (substr($textFile, -4) == '.txt') {
        $data = file_get_contents('includes/' . $textFile);
        $data = explode("\n", $data);
        $header = array_shift($data) . ' (' . sizeof($data) . ')';
        $value = substr($textFile, 0, -4);

        if (!$textOptions) {
            $checked = ' checked';
        } else {
            $checked = '';
        }
        $textOptions .= '<input type="radio" name="nameAllCountries" value="' . $value . '"' . $checked . '/>' . $header . '<br />';

    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Railroads Online Map</title>
    <link rel="stylesheet" href="/assets/css/reset.css?<?php echo filemtime('assets/css/reset.css'); ?>"/>
    <link rel="stylesheet" href="/assets/css/main.css?<?php echo filemtime('assets/css/main.css'); ?>"/>
    <link rel="stylesheet" href="/assets/css/export.css?<?php echo filemtime('assets/css/export.css'); ?>"/>
    <script type="text/javascript" src="/assets/js/js.cookie.min.js"></script>
    <script>
        const cookies = Cookies.withAttributes({
            path: '/',
            secure: true,
            expires: 30
        })
    </script>

</head>
<body class="export" bgcolor="black">
<main class="export__main">

    <div class="export__nav">
        <button class="button button--toggle" id="info-panel-toggle">Info</button>
        <button class="button button--toggle" id="edit-panel-toggle">Edit</button>
    </div>

    <div class="export__panel info-panel">
        <div class="export__panel-scroll-content">
            <details>
                <summary><h4>Player Info</h4></summary>
                <div class="export__panel--player-list">
                    <?php
                    foreach ($dataArray['Players'] as $index => $player) {
                        ?>
                        <div class="export__panel--player-list-item" style="text-decoration: underline"
                             onclick="zoomTo(<?php echo
                                 (((400000 - ($player['Location'][0] + 200000)) / 400000)) . ',' . (((400000 - ($player['Location'][1] + 200000)) / 400000));
                             ?>)"><?= $player['Name']; ?></div>
                        <div class="export__panel--player-list-item"><?= $player['Xp']; ?> XP</div>
                        <div class="export__panel--player-list-item"><?= $player['Money']; ?> $</div>
                        <?php
                    }
                    ?>
                </div>
                <div>
                    <input id="players_default" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('players_default'); ?>/>
                    Show players
                </div>

            </details>
            <details>
                <summary><h4><?php
                        echo sizeof($tasks[0]);
                        $sum = 0;
                        foreach ($tasks[0] as $task) {
                            $sum += $task[1];
                        } ?> Delivery Task<?php echo plural($sum); ?> ($<?php echo $sum; ?>)</h4></summary>
                <ul>
                    <?php
                    foreach ($tasks[0] as $task) {
                        echo '<li>' . $task[0] . '</li>';
                    }
                    ?>
                </ul>

            </details>
            <details>
                <summary><h4><?php
                        $sum = 0;
                        foreach ($tasks[1] as $industry => $tasksInner) {
                            foreach ($tasksInner as $t) {
                                $sum += $t[1];
                            }
                        }
                        echo sizeof($tasks[1]); ?> Construction Task<?php echo plural(sizeof($tasks[1])); ?>
                        ($<?php echo $sum; ?>)</h4></summary>
                <ul>
                    <?php
                    foreach ($tasks[1] as $industry => $tasksInner) {
                        $subAmount = 0;
                        foreach ($tasksInner as $i => $t) {
                            $subAmount += $t[1];
                        }
                        echo '<li>Build a track to ' . $industry . ' (potential $' . $subAmount . ')</li>';
                    }
                    ?>
                </ul>
            </details>
            <details>
                <summary><h4><?php
                        echo sizeof($tasks[2]);
                        $sum = 0;
                        foreach ($tasks[2] as $task) {
                            $sum += $task[1];
                        } ?> Expansion Task<?php echo plural(sizeof($tasks[2])); ?> (-$<?php echo $sum; ?>)</h4>
                </summary>
                <ul>
                    <?php
                    foreach ($tasks[2] as $task) {
                        echo '<li>' . $task[0] . '</li>';
                    }
                    ?>
                </ul>

            </details>
            <details>
                <script>
                    function zoomTo(x, y) {
                        panZoom = svgPanZoom("#demo-tiger", {
                            zoomEnabled: true,
                            controlIconsEnabled: false,
                            fit: true,
                            center: true,
                            maxZoom: 20
                        });
                        x = Math.round(-5 * x * screen.availWidth + (screen.availWidth / 2));
                        y = Math.round(-5 * y * screen.availWidth + (screen.availHeight / 2));
                        let point = {x: x, y: y};
                        panZoom.zoom(5);
                        panZoom.center();
                        panZoom.pan(point);

                    }
                </script>
                <summary><h4><?php
                        echo sizeof($tasks[3]);
                        $sum = 0;
                        foreach ($tasks[3] as $task) {
                        } ?> Find and Recover Task<?php echo plural(sizeof($tasks[3])); ?></h4>
                </summary>
                <ul>
                    <?php
                    foreach ($tasks[3] as $task) {
                        echo '<li><span onclick="zoomTo(' .
                            (((400000 - ($task[1]['x'] + 200000)) / 400000)) . ', ' .
                            (((400000 - ($task[1]['y'] + 200000)) / 400000)) .
                            ')">[?] </span>' . $task[0] . '</li>';
                    }
                    ?>
                </ul>

            </details>

            <hr/>
            <div class="message--alert info">
                All settings below are stored in cookies and will be applied on each map you visit.
            </div>

            <details>
                <summary><h4>Change background</h4></summary>
                <div class="export__panel--bg-grid">
                    <div class="box">
                        <img id="bg" src="/assets/images/bg_90x90.png" width="90" height="90" alt="Old background"
                             onclick="changeBackground(this)">
                    </div>
                    <div class="box">
                        <img id="bg3" src="/assets/images/bg3_90x90.png" width="90" height="90" alt="New background"
                             onclick="changeBackground(this)">
                    </div>
                    <div class="box">
                        <img id="bg4" src="/assets/images/bg4_90x90.png" width="90" height="90" alt="Psawhns background"
                             onclick="changeBackground(this)">
                    </div>
                    <div class="box">
                        <img id="bg5" src="/assets/images/bg5_90x90.png" width="90" height="90"
                             alt="Psawhns background with kanados" onclick="changeBackground(this)">
                    </div>
                    <div class="box">
                        <img id="bg6" src="/assets/images/bg6_90x90.jpg" width="90" height="90"
                             alt="Psawhns background with kanados" onclick="changeBackground(this)">
                    </div>
                </div>
            </details>

            <details>
                <summary><h4>Trees</h4></summary>
                <div>
                    <input id="trees_user" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('trees_user'); ?>/>
                    Show
                    trees
                    cut down more than <?php echo (int)($_COOKIE['treeMap90'] / 100); ?>m of industry (can be replanted)
                </div>
                <div>
                    <input id="trees_default" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('trees_default'); ?>/>
                    Show
                    trees cut down less than <?php echo (int)($_COOKIE['treeMap90'] / 100); ?>m of industry
                </div>
            </details>

            <details>
                <summary><h4>Rails and beds</h4></summary>
                <div>
                    <input id="beds" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('beds'); ?>/> Show beds
                </div>
                <div>
                    <input id="tracks" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('tracks'); ?>/> Show
                    tracks
                </div>
                <div>
                    <input id="switches" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('switches'); ?>/> Show
                    switches
                    and
                    crossings
                </div>
                <div>
                    <input id="ironOverWood" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('ironOverWood'); ?>/>
                    Show
                    Iron
                    bridge on
                    top of Wood bridge
                </div>

            </details>

            <details>
                <summary><h4>Locos and carts</h4></summary>
                <div>
                    <input id="rollingstock" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('rollingstock'); ?>/>
                    Show
                    rolling
                    stock
                </div>

                <h4>Cart coloring</h4>
                <div class="cart_colors">
                    <?php
                    $images = array(
                        "ce_flatcar_logs" => "flatcar_logs.png",
                        "cf_flatcar_logs" => "flatcar_logs_loaded.png",
                        "ce_flatcar_stakes" => "flatcar_stakes.png",
                        "cf_flatcar_stakes" => "flatcar_stakes_loaded.png",
                        "ce_flatcar_hopper" => "flatcar_hopper_empty.png",
                        "cf_flatcar_hopper" => "flatcar_hopper.png",
                        "ce_flatcar_cordwood" => "flatcar_cordwood.png",
                        "cf_flatcar_cordwood" => "flatcar_cordwood_loaded.png",
                        "ce_flatcar_tanker" => "flatcar_tanker_empty.png",
                        "cf_flatcar_tanker" => "flatcar_tanker.png",
                        "ce_boxcar" => "boxcar_empty.png",
                        "cf_boxcar" => "boxcar.png",
                        "ce_caboose" => "caboose.png",
                        "cf_caboose" => "caboose.png",
                    );

                    foreach (array('flatcar_logs', 'flatcar_stakes', 'flatcar_hopper', 'flatcar_cordwood', 'flatcar_tanker', 'boxcar', 'caboose') as $cartType) {
                        ?>
                        <label for="ce_<?= $cartType; ?>">
                            <img src="/assets/images/<?= $images['ce_' . $cartType]; ?>" width="72" height="72"/>
                        </label>
                        <input type="color" value="<?php color_cookie_or_default('ce_' . $cartType); ?>"
                               id="ce_<?= $cartType; ?>" onchange="updateColor(this)">
                        <label for="cf_<?= $cartType; ?>">
                            <img src="/assets/images/<?= $images['cf_' . $cartType]; ?>" width="72" height="72"/>
                        </label>
                        <input type="color" value="<?php color_cookie_or_default('cf_' . $cartType); ?>"
                               id="cf_<?= $cartType; ?>" onchange="updateColor(this)">
                    <?php } ?>
                </div>
                <button class="button" onclick="shareColors()">Share color scheme</button>
                <button class="button" onclick="showImportModal()">Import color scheme</button>
            </details>
            <details>
                <summary><h4>Industries</h4></summary>
                <div>
                    <input id="industryLabel" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('industryLabel'); ?>/>
                    Show Industry Labels
                </div>

            </details>

            <details>
                <summary><h4>Slopes</h4></summary>
                <div>
                    <input id="slopeLabel0" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel0'); ?>/>
                    Show
                    Slope
                    Labels 0% to
                    1%
                </div>
                <div>
                    <input id="slopeLabel1" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel1'); ?>/>
                    Show
                    Slope
                    Labels 1% to
                    2%
                </div>
                <div>
                    <input id="slopeLabel2" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel2'); ?>/>
                    Show
                    Slope
                    Labels
                    2% to 3%
                </div>
                <div>
                    <input id="slopeLabel3" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel3'); ?>/>
                    Show
                    Slope
                    Labels
                    above 3%
                </div>
                <div>
                    <input id="slopeLabel4" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel4'); ?>/> I
                    want
                    to
                    brag with my slope using 6 decimals after the comma
                </div>
                <div>
                    <input id="maxSlopeLabel" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('maxSlopeLabel'); ?>/>
                    Show orange max slope circles
                </div>

            </details>
            <details>
                <summary><h4>Curves</h4></summary>
                <div>
                    <input id="slopeLabel5" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel5'); ?>/>
                    Curve radius 0..40m
                </div>
                <div>
                    <input id="slopeLabel6" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel6'); ?>/>
                    Curve radius 40..60m
                </div>
                <div>
                    <input id="slopeLabel7" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel7'); ?>/>
                    Curve radius 60..120m
                </div>
                <div>
                    <input id="slopeLabel8" type="checkbox"
                           onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel8'); ?>/>
                    Curve radius 120..xm
                </div>
            </details>

            <hr/>
            <h5>
                The settings below will require a refresh of the page
            </h5>
            <div>
                <label for="labelPrefix">Text label prefix: </label>
                <input id="labelPrefix" placeholder=".."
                       value="<?php (isset($_COOKIE['labelPrefix']) && $_COOKIE['labelPrefix'] != '') ? print($_COOKIE['labelPrefix']) : print('..'); ?>"/>
            </div>
            <div>
                <label for="treeMap90">Tree to industry min distance (cm): </label>
                <input id="treeMap90" placeholder="9000" size="4"
                       value="<?php (isset($_COOKIE['treeMap90']) && $_COOKIE['treeMap90'] != '') ? print($_COOKIE['treeMap90']) : print('9000'); ?>"/>
            </div>
            <div>
                <label for="treeMap7">Tree to track min distance (cm): </label>
                <input id="treeMap7" placeholder="700" size="4"
                       value="<?php (isset($_COOKIE['treeMap7']) && $_COOKIE['treeMap7'] != '') ? print($_COOKIE['treeMap7']) : print('700'); ?>"/>
            </div>
            <button class="button" onclick="applySettings()">Apply and refresh</button>
        </div>
    </div>

    <div id="container" class="export__map">
        <svg id="demo-tiger" class="export__map-viewer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8000 8000">
            <script type="text/JavaScript">
                <![CDATA[
                let thingy = 0;

                function my_function(spline, segment) {
                    svg = document.getElementById('demo-tiger');
                    var children = svg.children[1].children[16].children;
//                    alert('You clicked on Segment ' + spline + '-' + segment);
                    for (var i = 0; i < children.length; i++) {
                        var tableChild = children[i];
                        if (
                            '' + tableChild.getAttribute('sp') === '' + spline &&
                            '' + tableChild.getAttribute('se') === '' + segment
                        ) {
                            if (tableChild.getAttribute('stroke') === 'lightblue') {
                                tableChild.setAttribute('stroke', 'black');
                                if (thingy === 1) {
                                    elem = document.getElementById('curveFrom');
                                    elem.value = '0-0';
                                    thingy = 0;
                                }
                                if (thingy === 2) {
                                    elem = document.getElementById('curveTo');
                                    elem.value = '0-0';
                                    thingy = 1;
                                }
                            } else {
                                tableChild.setAttribute('stroke', 'lightblue');
                                if (thingy === 1) {
                                    thingy = 2;
                                    elem = document.getElementById('curveTo');
                                    elem.value = spline + '-' + segment;
                                }
                                if (thingy === 0) {
                                    thingy = 1;
                                    elem = document.getElementById('curveFrom');
                                    elem.value = spline + '-' + segment;
                                }
                            }
                        }
                    }

                }

                ]]>
            </script>
            <defs>
                <pattern id="bild" x="0" y="0" width="8000" height="8000" patternUnits="userSpaceOnUse">
                    <image x="0" y="0" width="8000" height="8000" href="/assets/images/bg5.jpg"/>
                </pattern>
                <style>
                    .cls-pla, .cls-2, .cls-noFill {
                        stroke: #231f20;
                        stroke-miterlimit: 10;
                    }

                    .cls-pla, .cls-noFill {
                        stroke-width: 0.25px;
                    }

                    .cls-2 {
                        fill: #303030;
                        stroke-width: 0.5px;
                    }
                    .cls-lake{fill:#0069a4;}
                    .cls-build{stroke:#231f20;stroke-miterlimit:10;}
                    .cls-build2{fill:#673714;}
                    .cls-pla{fill:#a65926;}
                    .cls-roof{fill:#303030;}
                    .cls-noFill{fill:none;}


                </style>

            </defs>
            <rect x="0" y="0" width="8000" height="8000" fill="url(#bild)" stroke="black"/>

            <?php
            $loggingCampHtml = '<g id="LoggingCamp" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1.6)" class="industryLabel">
<rect class="cls-1" x="56.26" y="82.38" width="3.83" height="24.77"/>
<rect id="Logs2" class="cls-pla" x="56.11" y="56.36" width="3.83" height="12.35"/>
<rect id="Logs1" class="cls-pla" x="56.01" y="38.55" width="3.83" height="12.35"/>
<rect id="Cordwood1" class="cls-pla" x="37.42" y="74.11" width="3.83" height="12.35"/>
<rect id="Cordwood2" class="cls-pla" x="37.42" y="90.36" width="3.83" height="12.35"/>
<rect class="cls-pla" x="43.55" y="71.67" width="8.31" height="1.97" transform="translate(-2.51 1.71) rotate(-2)"/>
<rect class="cls-pla" x="45.06" y="107.76" width="8.31" height="1.97" transform="translate(-3.76 1.78) rotate(-2)"/>

<polygon class="cls-2" points="49.34 3.44 54.09 2.5 58.81 3.78 58.22 17.56 53.38 16.44 48.63 17.16 49.34 3.44"/>
<path class="build2" d="M54.09,2.5l-.71,13.94Z"/>
<polygon class="cls-2" points="20.66 23.72 22.5 23.72 24.78 24.38 26 15.59 23.5 14.84 21.91 15.03 20.66 23.72"/>
<polygon class="cls-2" points="20.84 30.38 21.91 39.16 23.59 38.81 26 38.78 25.09 29.91 22.97 29.91 20.84 30.38"/>
<polygon class="cls-2" points="13.94 49.59 13.09 54.13 13.72 59 27.59 59.5 27.09 54.69 27.78 49.94 13.94 49.59"/>
<polygon class="cls-2" points="21.75 67.63 22.69 76.38 25 76.19 26.81 76.03 26.09 67.22 23.78 67.41 21.75 67.63"/>
<polygon class="cls-2" points="53.41 69.78 55.63 69.78 57.69 69.78 58.02 78.44 55.84 78.44 53.72 78.44 53.41 69.78"/>
<polygon class="cls-2" points="70.03 31.66 72.34 31.66 74.16 31.91 73.69 40.75 71.66 40.41 69.41 40.44 70.03 31.66"/>
<line class="build2" x1="23.78" y1="14.93" x2="22.5" y2="23.72"/>
<line class="build2" x1="22.97" y1="29.91" x2="23.59" y2="38.81"/>
<line class="build2" x1="13.09" y1="54.13" x2="27.09" y2="54.69"/>
<line class="build2" x1="23.78" y1="67.41" x2="25" y2="76.19"/>
<line class="build2" x1="55.63" y1="69.78" x2="55.84" y2="78.44"/>
<line class="build2" x1="72.34" y1="31.66" x2="71.66" y2="40.41"/>
<polygon class="cls-2" points="46.03 25.08 54.83 24.89 54.83 26.92 54.83 29.25 46.16 29.44 46.16 27.16 46.03 25.08"/>
<line class="build2" x1="46.16" y1="27.16" x2="54.83" y2="27.03"/>
<polygon class="cls-2" points="68.58 45.02 70.71 45.02 72.88 45.21 72.75 54.1 70.73 54.1 68.44 53.96 68.58 45.02"/>
<line class="build2" x1="70.71" y1="45.02" x2="70.73" y2="54.1"/>
</g> ';
            
            
            $refineryHtml = '<g id="Refinery" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1.8)" class="industryLabel">
                <rect id="Crude_Oil" data-name="Crude Oil" class="cls-pla" x="65.77" y="13.14" width="3.41" height="22.56" transform="translate(91.89 -43.05) rotate(90)"/>
                <rect id="Steel_Pipes" data-name="Steel Pipes" class="cls-pla" x="35.52" y="19.89" width="3.41" height="22.56" transform="translate(68.39 -6.05) rotate(90)"/>
                <rect id="Lumber" class="cls-pla" x="11.13" y="31.86" width="3.41" height="11.28" transform="translate(50.33 24.68) rotate(90)"/>

                <polygon id="Oil_Barrels" data-name="Oil Barrels" class="cls-pla" points="76.44 76.5 75.5 76.5 75.5 79.97 98.31 79.97 98.06 65.38 88 65.38 88.06 76.34 86.62 76.33 86.53 65.38 76.44 65.38 76.44 76.5"/>

                <rect class="cls-2" x="99.17" y="66.77" width="4.63" height="9.5"/>
                <line class="cls-noFill" x1="101.48" y1="66.77" x2="101.48" y2="76.27"/>
                <rect class="cls-2" x="96.73" y="42.04" width="12.92" height="8.83"/>
                <line class="cls-noFill" x1="96.73" y1="46.46" x2="109.69" y2="46.46"/>

                <circle class="cls-2" cx="57.14" cy="32.41" r="5.95"/>
                <circle class="cls-2" cx="72.8" cy="32.41" r="5.95"/>
                <circle class="cls-2" cx="71.54" cy="61.98" r="1.36"/>
                <circle class="cls-2" cx="47.11" cy="61.98" r="1.36"/>

                <polygon class="cls-2" points="43.56 47.44 40.59 47.44 40.59 55.97 48.47 56.19 48.47 49.08 50.25 49.08 50.25 43.13 43.6 43.13 43.56 47.44"/>
                <polygon class="cls-2" points="69.02 47.44 66.05 47.44 66.05 55.97 73.93 56.19 73.93 49.08 75.71 49.08 75.71 43.13 69.06 43.13 69.02 47.44"/>
                <polygon class="cls-2" points="88.65 35.39 88.65 38.36 97.18 38.36 97.4 30.48 90.29 30.48 90.29 28.71 84.33 28.71 84.33 35.36 88.65 35.39"/>
            </g>';

            $smelterHtml = '<g id="Smelter" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1.4)" class="industryLabel">
<rect id="RailsRawIron" class="cls-pla" x="4.36" y="24.09" width="4.39" height="59.1"/>
<rect id="Iron" class="cls-pla" x="107.73" y="14.96" width="4.39" height="29.66"/>
<rect id="Cordwood" class="cls-pla" x="97.8" y="64.34" width="4.39" height="29.6"/>

<path class="cls-2" d="M53,33.31a5.3,5.3,0,0,1,10.56.07"/>
<polygon class="cls-2" points="45.69 34.5 58.25 32.75 71.21 34.5 71.25 91.8 58.18 94.29 45.69 91.81 45.69 34.5"/>
<line class="cls-noFill" x1="58.15" y1="32.75" x2="58.15" y2="94.44"/>
<rect class="cls-2" x="39.22" y="42.67" width="6.47" height="17.29"/>
<rect class="cls-2" x="39.22" y="64.83" width="6.47" height="17.29"/>
<rect class="cls-2" x="71.25" y="41.67" width="6.47" height="17.29"/>
</g>';

            $oilfieldHtml = '<g id="Oilfield" transform="translate(###X###,###Y###) rotate(###ROT###) scale(3)" class="industryLabel">
<rect id="Steel_Pipes_Beams" data-name="Steel Pipes Beams" class="cls-pla" x="79.27" y="53.58" width="14.38" height="2.21"/>
<rect id="Tools" class="cls-pla" x="99.04" y="53.58" width="7.29" height="2.21"/>
<rect id="Crude_Oil" data-name="Crude Oil" class="cls-pla" x="70.91" y="65.23" width="9.88" height="1.19"/>
<circle class="cls-2" cx="63.36" cy="50.92" r="3.67"/>
<circle class="cls-2" cx="72.55" cy="50.71" r="3.65"/>
<polygon class="cls-2" points="78.97 46.53 78.97 41.91 85.91 41.91 91.03 41.91 91.03 46.53 78.97 46.53"/>
<line class="cls-2" x1="86.68" y1="46.53" x2="86.68" y2="41.91"/>
<rect class="cls-2" x="87.81" y="43.13" width="2.17" height="2.17"/>
<line class="cls-noFill" x1="89.98" y1="43.13" x2="91.07" y2="42.05"/>
<line class="cls-noFill" x1="89.98" y1="45.3" x2="91.08" y2="46.41"/>
<line class="cls-noFill" x1="87.81" y1="45.3" x2="86.63" y2="46.48"/>
<line class="cls-noFill" x1="87.81" y1="43.13" x2="86.69" y2="42.01"/>
<polygon class="cls-2" points="47.97 85.28 47.97 80.66 54.91 80.66 60.04 80.66 60.04 85.28 47.97 85.28"/>
<line class="cls-2" x1="55.69" y1="85.28" x2="55.69" y2="80.66"/>
<rect class="cls-2" x="56.82" y="81.88" width="2.17" height="2.17"/>
<line class="cls-noFill" x1="58.99" y1="81.88" x2="60.07" y2="80.8"/>
<line class="cls-noFill" x1="58.99" y1="84.05" x2="60.09" y2="85.16"/>
<line class="cls-noFill" x1="56.82" y1="84.05" x2="55.64" y2="85.23"/>
<line class="cls-noFill" x1="56.82" y1="81.88" x2="55.69" y2="80.76"/>
<polygon class="cls-2" points="105.34 90.11 100.71 90.11 100.71 83.17 100.71 78.05 105.34 78.05 105.34 90.11"/>
<line class="cls-2" x1="105.34" y1="82.4" x2="100.71" y2="82.4"/>
<rect class="cls-2" x="101.94" y="79.1" width="2.17" height="2.17" transform="translate(22.84 183.21) rotate(-90)"/>
<line class="cls-noFill" x1="101.94" y1="79.1" x2="100.85" y2="78.01"/>
<line class="cls-noFill" x1="104.11" y1="79.1" x2="105.21" y2="78"/>
<line class="cls-noFill" x1="104.11" y1="81.27" x2="105.29" y2="82.45"/>
<line class="cls-noFill" x1="101.94" y1="81.27" x2="100.82" y2="82.39"/>
<polygon class="cls-2" points="88 17.03 84.73 20.3 79.82 15.4 76.2 11.77 79.47 8.5 88 17.03"/>
<line class="cls-2" x1="82.54" y1="11.58" x2="79.27" y2="14.85"/>
<rect class="cls-2" x="78.26" y="10.57" width="2.17" height="2.17" transform="translate(127.21 75.99) rotate(-135)"/>
<line class="cls-noFill" x1="77.81" y1="11.65" x2="76.27" y2="11.65"/>
<line class="cls-noFill" x1="79.34" y1="10.12" x2="79.34" y2="8.56"/>
<line class="cls-noFill" x1="80.88" y1="11.65" x2="82.54" y2="11.65"/>
<line class="cls-noFill" x1="79.34" y1="13.18" x2="79.34" y2="14.77"/>
<polygon class="cls-2" points="0.71 32.13 3.6 28.51 9.02 32.84 13.03 36.03 10.14 39.65 0.71 32.13"/>
<line class="cls-2" x1="6.74" y1="36.94" x2="9.63" y2="33.32"/>
<rect class="cls-2" x="8.83" y="35.43" width="2.17" height="2.17" transform="translate(24.93 1.79) rotate(38.58)"/>
<line class="cls-noFill" x1="11.44" y1="36.34" x2="12.97" y2="36.17"/>
<line class="cls-noFill" x1="10.09" y1="38.03" x2="10.26" y2="39.58"/>
<line class="cls-noFill" x1="8.39" y1="36.68" x2="6.73" y2="36.87"/>
<line class="cls-noFill" x1="9.74" y1="34.99" x2="9.56" y2="33.41"/>
<polygon class="cls-2" points="54.61 26.31 59.22 25.96 59.75 32.87 60.14 37.98 55.53 38.34 54.61 26.31"/>
<line class="cls-2" x1="55.2" y1="34" x2="59.81" y2="33.65"/>
<rect class="cls-2" x="56.59" y="34.95" width="2.17" height="2.17" transform="translate(89.2 -24.22) rotate(85.63)"/>
<line class="cls-noFill" x1="58.83" y1="37.03" x2="60" y2="38.03"/>
<line class="cls-noFill" x1="56.67" y1="37.19" x2="55.66" y2="38.38"/>
<line class="cls-noFill" x1="56.51" y1="35.03" x2="55.24" y2="33.95"/>
<line class="cls-noFill" x1="58.67" y1="34.87" x2="59.7" y2="33.66"/>
<polygon class="cls-2" points="91.37 92.29 92.05 96.86 85.18 97.89 80.11 98.64 79.44 94.06 91.37 92.29"/>
<line class="cls-2" x1="83.74" y1="93.42" x2="84.42" y2="98"/>
<rect class="cls-2" x="80.8" y="94.95" width="2.17" height="2.17" transform="matrix(-0.99, 0.15, -0.15, -0.99, 177, 178.99)"/>
<line class="cls-noFill" x1="80.97" y1="97.27" x2="80.06" y2="98.5"/>
<line class="cls-noFill" x1="80.66" y1="95.12" x2="79.4" y2="94.19"/>
<line class="cls-noFill" x1="82.8" y1="94.81" x2="83.79" y2="93.47"/>
<line class="cls-noFill" x1="83.12" y1="96.95" x2="84.4" y2="97.9"/>
<polygon class="cls-2" points="104.11 20.15 108.67 20.95 107.46 27.79 106.57 32.83 102.01 32.03 104.11 20.15"/>
<line class="cls-2" x1="102.77" y1="27.75" x2="107.32" y2="28.55"/>
<rect class="cls-2" x="103.58" y="29.24" width="2.17" height="2.17" transform="translate(152.75 -67.45) rotate(100.03)"/>
<line class="cls-noFill" x1="105.54" y1="31.58" x2="106.42" y2="32.84"/>
<line class="cls-noFill" x1="103.41" y1="31.21" x2="102.13" y2="32.1"/>
<line class="cls-noFill" x1="103.78" y1="29.07" x2="102.83" y2="27.71"/>
<line class="cls-noFill" x1="105.92" y1="29.45" x2="107.22" y2="28.54"/>
</g>';

            $sawmillHtml = ' <g id="Sawmill" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1.7)" class="industryLabel">

<path class="cls-lake" d="M68.25,112.88c2.8,1.63,2.91,2.79,6.44,4.75,2.25,1.24,4,2.2,6.37,2.25A11.61,11.61,0,0,0,88.5,117a15,15,0,0,0,4.69-7.25c2.06-6,.29-8.32.44-18.37.1-6.87,1-9.38.25-16.38-.46-4.41-1.13-6.42-2.57-8.12-2.54-3-6.28-3.58-9.87-4.13-5-.77-8.78-.06-13.94.94-6,1.17-9,1.75-11.62,4s-3.57,4.59-5.25,8.37a30.16,30.16,0,0,0-3,10,26.13,26.13,0,0,0,3.06,15.13c1.46,2.63,3.8,6.86,8.5,9C62.86,111.85,64.35,110.6,68.25,112.88Z"/>

<rect class="cls-unknown" x="62.81" y="52.28" width="2.25" height="14.95"/>

<rect id="Logs" class="cls-pla" x="92.56" y="63.72" width="3.54" height="45.87"/>
<rect id="Beams" class="cls-pla" x="20.16" y="7.72" width="3.54" height="23.22"/>
<rect id="Lumber" class="cls-pla" x="20.16" y="38.41" width="3.54" height="23.22"/>

<polygon class="cls-build2" points="22.34 29.97 50.19 29.97 50.19 33.94 50.19 38.53 22.34 38.81 21.13 33.72 22.34 29.97"/>
<polygon class="cls-build2" points="50.19 20.84 50.19 53.22 63.83 53.06 77.66 53 77.66 20.84 63.83 19.84 50.19 20.84"/>
<polygon class="cls-build2" points="51.97 19.84 51.97 24.94 51.97 32.16 57.63 32.16 57.63 44.25 64.03 44.25 70.09 44.25 70.09 32.05 75.81 32.05 75.81 24.91 75.81 19.84 51.97 19.84"/>
<polygon class="cls-build2" points="94.59 24.59 95.81 29.41 94.59 36.03 77.78 36.03 77.78 29.38 77.78 24.59 94.59 24.59"/>
<line class="cls-roof" x1="21.13" y1="33.72" x2="50.19" y2="33.72"/>
<line class="cls-roof" x1="95.81" y1="29.41" x2="77.78" y2="29.38"/>
<polyline class="cls-noFill" points="63.83 53.06 63.83 44.25 63.83 25.09 52.28 25.09 75.69 25.09"/>
<polyline class="cls-noFill" points="57.63 32.16 63.83 25.09 70.31 32.05"/>
</g>';
            
            $ironworksHtml = '<g id="Ironworks" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1.5)" class="industryLabel">
<polygon class="cls-build" points="8.31 26.38 8.31 60.63 8.31 70.69 19.19 70.69 19.19 60.88 49.31 60.88 49.31 26.38 8.31 26.38"/>
<polyline class="cls-build" points="8.31 34.92 49.31 34.92 49.31 49.42 8.31 49.42 8.31 34.92"/>
<line class="cls-2" x1="8.31" y1="42.17" x2="49.31" y2="42.17"/>
<polyline class="cls-build" points="8.31 70.81 8.31 75.15 12.34 75.15 12.34 70.81 15.12 70.81 15.12 75.15 19.19 75.15 19.19 70.81"/>
<circle class="cls-build2" cx="10.32" cy="72.82" r="1.22"/>
<circle class="cls-build2" cx="17.16" cy="72.82" r="1.22"/>
<rect class="cls-build" x="63.42" y="26.38" width="41.25" height="34.96"/>
<polyline class="cls-build" points="63.54 34.92 104.54 34.92 104.54 49.42 63.54 49.42 63.54 34.92"/>
<line class="cls-2" x1="63.54" y1="42.17" x2="104.54" y2="42.17"/>

<polygon id="Raw_Iron_Coal" data-name="Raw Iron Coal" class="cls-pla" points="31.19 80.75 31.19 84.92 89.1 84.92 89.1 80.75 62.6 80.75 62.6 57.63 58.59 57.63 58.59 80.71 31.19 80.75"/>
<rect class="cls-pla" x="91.78" y="61.33" width="4.13" height="11.48"/>
<rect id="Tools" class="cls-pla" x="38.25" y="7.88" width="13.58" height="3.79"/>
<polygon id="Steel_Pipes" data-name="Steel Pipes" class="cls-pla" points="74.88 7.04 74.88 11.04 97.63 11.04 97.63 23.96 101.92 23.96 101.92 6.83 74.88 7.04"/>
<rect class="cls-pla" x="65.53" y="14.34" width="4.22" height="12.03"/>
</g>';
            
            $coalMineHtml = ' <g id="CoalMine" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1)" class="industryLabel">
<rect id="Beams" class="cls-pla" x="26.05" y="75.92" width="5.47" height="37.31" transform="translate(123.36 65.8) rotate(90)"/>
<rect id="Rails" class="cls-pla" x="92.55" y="83.73" width="5.47" height="18.94" transform="translate(188.48 -2.08) rotate(90)"/>
<rect class="cls-2" x="56.69" y="82.56" width="14.75" height="14.75"/>
<rect class="cls-2" x="57.65" y="82.69" width="10.44" height="11.2"/>
<line class="cls-build" x1="68.1" y1="93.89" x2="71.64" y2="97.31"/>
<rect class="cls-2" x="59.76" y="5.05" width="13.07" height="19.53"/>
<line class="cls-build2" x1="66.3" y1="5.05" x2="66.3" y2="24.25"/>
</g>';
            $ironMineHtml = ' <g id="IronMine" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1)" class="industryLabel">
<polygon class="cls-build" points="27 54.13 29.34 54.13 29.34 75.44 16.59 75.44 16.59 54.13 27 54.13"/>
<polygon class="cls-build" points="2.21 53.75 2.21 63.71 18.04 63.71 18.13 74.29 27.88 74.29 27.88 54.21 2.21 53.75"/>
<polyline class="cls-2" points="2.21 58.64 14.72 58.64 22.97 58.64 22.97 74.29"/>
<polygon class="cls-build" points="14.59 54.75 14.59 62.54 22.04 62.54 22.04 54.87 14.59 54.75"/>
<polygon class="cls-build" points="15.79 54.77 15.79 18.19 20.75 18.19 20.75 54.75 15.79 54.77"/>
<polygon class="cls-build" points="18.27 18.19 14.02 18.19 14.02 9.69 22.54 9.69 22.54 18.19 18.27 18.19"/>
<line class="cls-build2" x1="18.27" y1="9.69" x2="18.27" y2="18.19"/>
<line class="cls-build2" x1="18.42" y1="54.81" x2="18.42" y2="62.54"/>
<rect class="cls-build" x="35.16" y="57.56" width="20.09" height="13.75"/>
<line class="cls-2" x1="35.16" y1="64.44" x2="55.25" y2="64.44"/>
<rect id="Lumber" class="cls-pla" x="65.14" y="62.08" width="5.44" height="18.28" transform="translate(139.08 3.36) rotate(90)"/>
<rect id="Beams" class="cls-pla" x="97.36" y="62.24" width="5.44" height="18.28" transform="translate(171.45 -28.71) rotate(90)"/>
</g>';
            
            $freightDepotHtml ='<g id="FreightDepot" transform="translate(###X###,###Y###) rotate(###ROT###) scale(1.6)" class="industryLabel">
<polygon class="cls-pla" points="42.97 31.75 42.86 93.88 46.33 93.88 46.33 82.02 67.19 82.08 67.19 31.75 42.97 31.75"/>
<rect class="cls-build2" x="44.12" y="33.75" width="22.38" height="46.38"/>
<line class="cls-build" x1="55.31" y1="33.75" x2="55.31" y2="79.94"/>
</g>';
            
            foreach ($parser->goldenBucket['Industries'] as $industry) {
                $coordinateX = round(8000 - ($industry['Location'][0] + 200000) / 50);
                $coordinateY = round(8000 - ($industry['Location'][1] + 200000) / 50);
                switch ($industry['Type']) {
                    case 1:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX -100, $coordinateY+45, $industry['Rotation'][1]+180),
                            $loggingCampHtml
                        );
                        break;
                    case 2:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX -30, $coordinateY -152, $industry['Rotation'][1]+180),
                            $sawmillHtml
                        );
                        break;
                    case 3:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX -80 , $coordinateY -50, $industry['Rotation'][1]),
                            $smelterHtml
                        );
                        break;
                    case 4:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX-65 , $coordinateY+70, $industry['Rotation'][1]),
                            $ironworksHtml
                        );
                        break;
                    case 5:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX -230 , $coordinateY -185, $industry['Rotation'][1]),
                            $oilfieldHtml
                        );
                        break;
                    case 6:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX - 120, $coordinateY + 100, $industry['Rotation'][1] + 90),
                            $refineryHtml
                        );
                        break;
                    case 7:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX+95, $coordinateY-70, $industry['Rotation'][1]-90),
                            $coalMineHtml
                        );
                        break;
                    case 8:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX+65, $coordinateY+35, $industry['Rotation'][1]-90),
                            $ironMineHtml
                        );
                        break;
                    case 9:
                        echo str_replace(
                            array('###X###', '###Y###', '###ROT###'),
                            array($coordinateX-90, $coordinateY-100, $industry['Rotation'][1]-90),
                            $freightDepotHtml
                        );
                        break;
                    default:
                }
            }

            ?>
        </svg>
    </div>

    <div class="export__panel edit-panel">
        <div class="export__panel-scroll-content">
            <h3>Edit</h3><br/>
            <div class="edit-panel__extras">
                <details>
                    <summary><h4>Rolling Stock</h4></summary>
                    <h5>Locos</h5>
                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        <table id="rollingStockTable" class="export__mapper">
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Number</th>
                                <th>Near</th>
                                <th>Cargo</th>
                                <th>Amount</th>
                            </tr>
                        </table>
                        <button class="button">Apply Rolling Stock changes</button>
                    </form>
                    <h5>Carts</h5>
                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        <table id="rollingStockTable2" class="export__mapper">
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Number</th>
                                <th>Near</th>
                                <th>Cargo</th>
                                <th>Amount</th>
                            </tr>
                        </table>
                        <button class="button">Apply Rolling Stock changes</button>
                    </form>

                    <form method="POST" action="/converter.php"><input type="hidden" name="save"
                                                                       value="<?php echo $saveFile; ?>">
                        <?php echo $textOptions; ?>

                        <span style="font-size: smaller">Have another list? mail it to locolist@pordi.com</span><br>
                        apply to <select name="renameWhat">
                            <option value="everything">everything</option>
                            <option value="locos">only locomotives</option>
                            <option value="tenders">only tenders</option>
                            <option value="carts">only carts</option>
                            <option value="handcarts">only handcarts</option>
                        </select><br/>
                        <button class="button">Apply name schema</button>
                    </form>
                    <br>

                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        <input name="allBrakes" value="YES" type="hidden"/>
                        <button class="button">Apply all brakes</button>
                    </form>
                    <br>

                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        <table id="undergroundCartsTable" class="export__mapper"></table>
                        <button class="button">Get Carts from Underground</button>
                    </form>
                    <br/>

                </details>
                <br>

                <details>
                    <summary><h4>Trees</h4></summary>
                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        <input name="replant" value="700" size="4"> cm away from track!<br>
                        <input name="replant90" value="9000" size="4"> cm away from industries!<br>
                        <span style="font-size: smaller">for reference:<br> 65 cm washing machine, <br> 91 cm gauge,<br> 170 cm bathtub,<br> 460 cm car,<br> 1880 cm switch<br>
                    measured to start, center and end of track-(segment) only, switches, crosses are not taken into calculation (yet)</span><br>
                        <button class="button">Replant Trees</button>
                    </form>
                </details>
                <br>

                <details>
                    <summary><h4>Curves</h4></summary>
                    1) Zoom into your map on the left, click a piece of track (not a switch or cross).<br>
                    2) Click on another piece of track (not a switch or cross) - not too far away.<br>
                    3) Select options for type and height of bed.<br>
                    4) Click the button and then download your save.<br>
                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        from: <input name="from" id="curveFrom" value="0-0" size="6"><br>
                        to: <input name="to" id="curveTo" value="0-0" size="6"><br>
                        Track: <select name="curveTrack">
                            <option value="0">rail</option>
                            <option value="4">rail on wooden deck</option>
                        </select><br>
                        Bed: <select name="curveBed">
                            <option value="1">gravel</option>
                            <option value="5">stone wall</option>
                            <option value="3">wooden bridge</option>
                            <!--option value="7">steel bridge</option-->
                            <option value="none">No - bed! I want floating tracks</option>
                        </select><br>
                        sink tracks in bed?: <select name="sinkBed">
                            <option value="30">no</option>
                            <option value="15">yes</option>
                            <option value="22">a little</option>
                        </select><br>
                        The curve duplicates the start segment. Should the duplicate be visible?: <select
                                name="invisFirst">
                            <option value="no">No, cornery start is ok for me.</option>
                            <option value="yes">Yes, I want to delete the duplicate track I dont like.</option>
                        </select><br>
                        <button class="button">Generate curve and bed between segments</button>
                    </form>
                </details>
                <br>

                <details>
                    <summary><h4>Players</h4></summary>
                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        <table id="editPlayersTable" class="export__mapper">
                            <tr>
                                <th>Player</th>
                                <th>XP</th>
                                <th>Money</th>
                                <th>near</th>
                                <th>DO NOT CLICK</th>
                            </tr>
                        </table>
                        <button class="button">Apply Player Changes</button>
                    </form>
                </details>
                <br>

                <details>
                    <summary><h4>Industries</h4></summary>
                    <form method="POST" action="/converter.php">
                        <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                        <table id="industriesTable" class="export__mapper">
                            <tr>
                                <th>Industry</th>
                                <th>Item 1</th>
                                <th>Item 2</th>
                                <th>Item 3</th>
                                <th>Item 4</th>
                            </tr>
                        </table>
                        <button class="button">Apply Industry Changes</button>
                    </form>
                </details>
                <br>

                <a class="button" href="download.php?map=<?php echo substr(basename($saveFile), 0, -4); ?>">Download
                    Save</a>
            </div>
        </div>
    </div>

    <div class="export__controls">
        <button class="button" id="zoom-out">-</button>
        <button class="button" id="zoom-in">+</button>
        <button class="button" id="reset">Reset</button>
    </div>
</main>
<div id="shareCodeModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="shareCodeTitle">
            Give the following share code to your friends:
        </h3>
        <input type="text" style="width: 100%" id="shareCode"/>
        <span id="shareCodeError"></span>
        <button id="shareCodeSubmit" class="button">Apply</button>
    </div>
</div>
<script type="text/javascript" src="/assets/js/svg-pan-zoom.js.min.js"></script>
<script type="text/javascript" src="/assets/js/export.js?<?php echo filemtime('assets/js/export.js'); ?>"></script>

<?php
require_once 'utils/Minifier.php';
if (!file_exists('assets/js/mapper.min.js') || filemtime('assets/js/mapper.js') > filemtime('assets/js/mapper.min.js')) {
    $output = JShrink\Minifier::minify(file_get_contents('assets/js/mapper.js'));
    file_put_contents('assets/js/mapper.min.js', $output);
}
?>

<script type="text/javascript"
        src="/assets/js/mapper.js?<?php echo filemtime('assets/js/mapper.js'); ?>"></script>
<script type="text/javascript"
        src="/assets/js/colorshare.js?<?php echo filemtime('assets/js/colorshare.js'); ?>"></script>
<script type="text/javascript">
    const backgrounds = {
        'bg': [8000, 8000, 0, 0, 8000, 8000, 'bg.jpg'],
        'bg3': [8000, 8000, 0, 0, 8000, 8000, 'bg3.jpg'],
        'bg4': [8000, 8000, 0, 0, 8000, 8000, 'bg4.jpg'],
        'bg5': [8000, 8000, 0, 0, 8000, 8000, 'bg5.jpg'],
        'bg6': [8000, 8000, 0, 0, 8000, 8000, 'bg6.jpg']
    }
    const pattern = document.getElementsByTagName("pattern")[0];
    const image = document.getElementsByTagName("image")[0];


    map = new Mapper(<?php echo $json; ?>, cookies);
    map.drawSVG('demo-tiger');

    // Set different display options based on checkbox state or cookie value
    const options = document.getElementsByTagName("input");
    for (const element of options) {
        if (element.type === "checkbox" && typeof element.onclick == "function") {
            toggleDisplayOptions(element);
        }
    }
    if (cookies.get("bg") !== undefined) {
        changeBackground(cookies.get("bg"));
    }

    const colorInputs = document.getElementsByTagName("input");
    for (const input of colorInputs) {
        if (input.type === "color") {
            updateColor(input);
        }
    }


    function changeBackground(bgSelector) {
        let bg;
        if (bgSelector.id !== undefined) {
            bg = bgSelector.id;
        } else {
            bg = bgSelector;
        }
        if (bg in backgrounds) {
            pattern.setAttribute("width", backgrounds[bg][0].toString());
            pattern.setAttribute("height", backgrounds[bg][1].toString());
            image.setAttribute("x", backgrounds[bg][2].toString());
            image.setAttribute("y", backgrounds[bg][3].toString());
            image.setAttribute("width", backgrounds[bg][4].toString());
            image.setAttribute("height", backgrounds[bg][5].toString());
            image.setAttribute("href", "/assets/images/" + backgrounds[bg][6]);
            cookies.set("bg", bg);
        }
        if (bg === 'bg6') {
            var highlightedItems = document.querySelectorAll(".export__panel");
            highlightedItems.forEach(function (userItem) {
                userItem.style.setProperty('background', '#404040');
            });
            document.body.style.background = '#000000';
        }
    }

    function toggleOneElement(element, checkbox) {
        if (typeof element === "undefined") {
            return;
        }

        if (checkbox.checked) {
            element.classList.remove('display_hide');
            element.classList.add('display_show');
            cookies.set(checkbox.id, true);
        } else {
            element.classList.add('display_hide');
            element.classList.remove('display_show');
            cookies.set(checkbox.id, false);
        }
    }

    function toggleDisplayOptions(checkbox) {
        const element = document.getElementsByClassName(checkbox.id);
        for (let e of element) {
            toggleOneElement(e, checkbox);
        }

    }

    function updateColor(input) {
        const carts = document.getElementsByClassName(input.id);
        for (const cart of carts) {
            cart.setAttribute("fill", input.value);
        }
        cookies.set(input.id, input.value);
    }

    function applySettings() {
        const labelSettings = document.getElementById('labelPrefix');
        cookies.set('labelPrefix', labelSettings.value);
        const treeMap7Settings = document.getElementById('treeMap7');
        cookies.set('treeMap7', treeMap7Settings.value);
        const treeMap90Settings = document.getElementById('treeMap90');
        cookies.set('treeMap90', treeMap90Settings.value);
        location.reload();
    }
</script>
</body>
</html>
