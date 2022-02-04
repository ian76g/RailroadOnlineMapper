<?php
// server should keep session data for AT LEAST 1 hour
ini_set('session.gc_maxlifetime', 36000);
// each client should remember their session id for EXACTLY 1 hour
session_set_cookie_params(36000);
session_start();
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
//$_GET['name']='ian76g-10';
if (isset($_GET['name']) && $_GET['name'] != '') {
    $saveFile = "./saves/" . $_GET['name'] . ".sav";
    if (file_exists($saveFile)) {
        $slotExtension = explode('-', substr($saveFile, 0, -4));
        $slotExtension = '-' . $slotExtension[sizeof($slotExtension) - 1];
        $ah = new ArithmeticHelper();
        $parser = new GVASParser();
        $parser->owner = $_GET['name'];
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


$gID = array(
    0 => array(
        'iname' => 'sandhouse',
        'width' => 25,
        'height' => 25,
        'xoff' => 5,
        'yoff' => 5,
        'rotOff' => -90,
    ),
    1 => array(
        'iname' => 'loggingcamp',
        'width' => 190,
        'height' => 190,
        'xoff' => -10,
        'yoff' => -45,
        'rotOff' => 180,
    ),
    2 => array(
        'iname' => 'sawmill',
        'width' => 220,
        'height' => 220,
        'xoff' => -0,
        'yoff' => -0,
        'rotOff' => 180,
    ),
    3 => array(
        'iname' => 'smelter',
        'width' => 155,
        'height' => 155,
        'xoff' => -2,
        'yoff' => 25,
        'rotOff' => 0,
    ),
    4 => array(
        'iname' => 'ironworks',
        'width' => 170,
        'height' => 170,
        'xoff' => 20,
        'yoff' => -10,
        'rotOff' => 0,
    ),
    5 => array(
        'iname' => 'oilfield',
        'width' => 350,
        'height' => 350,
        'xoff' => -50,
        'yoff' => -15,
        'rotOff' => 0,
    ),
    6 => array(
        'iname' => 'refinery',
        'width' => 200,
        'height' => 200,
        'xoff' => -5,
        'yoff' => 15,
        'rotOff' => 90,
    ),
    7 => array(
        'iname' => 'coalmine',
        'width' => 113.39,
        'height' => 113.39,
        'xoff' => 35,
        'yoff' => -10,
        'rotOff' => -90,
    ),
    8 => array(
        'iname' => 'ironmine',
        'width' => 113.39,
        'height' => 113.39,
        'xoff' => -15,
        'yoff' => 35,
        'rotOff' => -90,
    ),
    9 => array(
        'iname' => 'freightdepot',
        'width' => 200,
        'height' => 200,
        'xoff' => 0,
        'yoff' => 0,
        'rotOff' => -90,
    ),
);

$genericIndustry = '<image href="assets/images/###INAME###.svg" id="###ID###" transform="translate(###X###,###Y###) rotate(###ROT###,###X2###,###Y2###)" width="###W###" height="###H###" class="industryLabel" />';

$industryHtml = '';
if (isset($parser->goldenBucket['Industries'])) {
    foreach ($parser->goldenBucket['Industries'] as $iKey => $industry) {
        $coordinateX = round(8000 - ($industry['Location'][0] + 200000) / 50);
        $coordinateY = round(8000 - ($industry['Location'][1] + 200000) / 50);
        switch ($industry['Type']) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
                $industryHtml .= str_replace(
                    array('###X###', '###Y###', '###ROT###', '###ID###', '###W###', '###H###', '###INAME###', '###X2###', '###Y2###'),
                    array(
                        $coordinateX - $gID[$industry['Type']]['width'] / 2 + $gID[$industry['Type']]['xoff'],
                        $coordinateY - $gID[$industry['Type']]['height'] / 2 + $gID[$industry['Type']]['yoff'],
                        $industry['Rotation'][1] + $gID[$industry['Type']]['rotOff'],
                        'iid-' . $iKey, $gID[$industry['Type']]['width'],
                        $gID[$industry['Type']]['height'],
                        $gID[$industry['Type']]['iname'],
                        $gID[$industry['Type']]['width'] / 2,
                        $gID[$industry['Type']]['height'] / 2
                    ),
                    $genericIndustry
                );
                break;
            default:
        }
    }
}
if (isset($parser->goldenBucket['Sandhouses'])) {

    foreach ($parser->goldenBucket['Sandhouses'] as $iKey => $industry) {
        $coordinateX = round(8000 - ($industry['Location'][0] + 200000) / 50);
        $coordinateY = round(8000 - ($industry['Location'][1] + 200000) / 50);
        switch ($industry['Type']) {
            case 0:
                $industryHtml .= str_replace(
                    array('###X###', '###Y###', '###ROT###', '###ID###', '###W###', '###H###', '###INAME###', '###X2###', '###Y2###'),
                    array(
                        $coordinateX - $gID[$industry['Type']]['width'] / 2 + $gID[$industry['Type']]['xoff'],
                        $coordinateY - $gID[$industry['Type']]['height'] / 2 + $gID[$industry['Type']]['yoff'],
                        $industry['Rotation'][1] + $gID[$industry['Type']]['rotOff'],
                        'iid-' . $iKey, $gID[$industry['Type']]['width'],
                        $gID[$industry['Type']]['height'],
                        $gID[$industry['Type']]['iname'],
                        $gID[$industry['Type']]['width'] / 2,
                        $gID[$industry['Type']]['height'] / 2
                    ),
                    $genericIndustry
                );
                break;
            default:
        }
    }
}
$industryHtml = str_replace(array("\n", "\r"), "", $industryHtml);
//$industryHtml = json_encode($industryHtml);

$dh = opendir('includes');
$textFiles = array();
while ($textFiles[] = readdir($dh)) ;
$textOptions = '';
foreach ($textFiles as $textFile) {
    if (substr($textFile, -4) == '.txt') {
        $data = file_get_contents('includes/' . $textFile);
        $data = explode("\n", $data);
        $header = array_shift($data) . ' (' . sizeof($data) . ') <A href="includes/'.$textFile.'" target="_preview">preview list</A>';
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
                    if(isset($dataArray['Players'])){
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
        <svg id="demo-tiger" class="export__map-viewer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8000 8000"
             onclick="alert_coords(evt)"
        >
            <script type="text/JavaScript">
                <![CDATA[
                let thingy = 0;

                function my_function(spline, segment) {
                    svg = document.getElementById('demo-tiger');
                    var children = svg.children[1].children[7].children;
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
            </defs>
            <rect x="0" y="0" width="8000" height="8000" fill="url(#bild)" stroke="black"/>

        </svg>
    </div>

    <script>
        var svg = document.getElementById('demo-tiger');
        var pt = svg.createSVGPoint();  // Created once for document

        function alert_coords(evt) {
            pt.x = evt.clientX;
            pt.y = evt.clientY;

            // The cursor point, translated into svg coordinates
            var cursorpt =  pt.matrixTransform(svg.getScreenCTM().inverse());
            console.log("(" + cursorpt.x + ", " + cursorpt.y + ")");
        }
    </script>

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
                    <summary><h4>Curves and Straights</h4></summary>
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
                            <option value="no">No, cornery start is ok for me. (Or I want a straight)</option>
                            <option value="yes">Yes, I want to delete the duplicate track I dont like.</option>
                        </select><br>
                        ignore the whole curve things - just draw a straight line: <input type="checkbox"
                                                                                          name="skipCurve"
                                                                                          name="skipCurve"/><br>
                        minimum height over ground (0 ignores terrain): <input name="minimumZ" id="minimumZ" value="0" size="6"><br>
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
                        <input type="checkbox" name="RAISEALL"> Raise all industries (DO NOT USE)<br>
                        <input type="checkbox" name="LOWERALL"> Lower all industries (DO NOT USE)<br>
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

    const industryHtml = '<?php echo $industryHtml; ?>';

    map = new Mapper(<?php echo $json; ?>, cookies);
    map.drawSVG('demo-tiger', industryHtml);

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
