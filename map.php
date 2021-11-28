<?php
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

while (!mkdir('lock')) {
}
file_put_contents('counter', $counter = file_get_contents('counter') + 1);
rmdir('lock');


$saveFile = null;
$json = null;

if (isset($_GET['name']) && $_GET['name'] != '') {
    $saveFile = "./saves/" . $_GET['name'] . ".sav";
    if (file_exists($saveFile)) {
        $slotExtension = explode('-', substr($saveFile, 0, -4));
        $slotExtension = '-' . $slotExtension[sizeof($slotExtension) - 1];

        $parser = new GVASParser();
        $json = $parser->parseData(file_get_contents($saveFile), false, $slotExtension);
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
</head>
<body class="export">
<main class="export__main">

    <div class="export__nav">
        <button class="button button--toggle" id="info-panel-toggle">Info</button>
        <button class="button button--toggle" id="edit-panel-toggle">Edit</button>
    </div>

    <div class="export__panel info-panel">
        <div class="export__panel-scroll-content">
            <h3>Player Info</h3>
            <div class="export__panel--player-list">
                <?php
                foreach ($dataArray['Players'] as $index => $player) {
                    ?>
                    <div class="export__panel--player-list-item"><?= $player['Name']; ?></div>
                    <div class="export__panel--player-list-item"><?= $player['Xp']; ?> XP</div>
                    <div class="export__panel--player-list-item"><?= $player['Money']; ?> $</div>
                    <?php
                }
                ?>
            </div>
            <hr/>
            <div class="message--alert info">
                All settings below are stored in cookies and will be applied on each map you visit.
            </div>
            <h3>Change background</h3>
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
            </div>
            <h3>Display options</h3>
            <div>
                <input id="trees_user" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('trees_user'); ?>/> Show
                trees
                cut down by
                player
            </div>
            <div>
                <input id="trees_default" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('trees_default'); ?>/> Show
                trees cut down by
                default
            </div>
            <div>
                <input id="beds" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('beds'); ?>/> Show beds
            </div>
            <div>
                <input id="tracks" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('tracks'); ?>/> Show tracks
            </div>
            <div>
                <input id="switches" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('switches'); ?>/> Show
                switches
                and
                crossings
            </div>
            <div>
                <input id="rollingstock" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('rollingstock'); ?>/> Show
                rolling
                stock
            </div>
            <div>
                <input id="slopeLabel0" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel0'); ?>/> Show
                Slope
                Labels 0% to
                1%
            </div>
            <div>
                <input id="slopeLabel1" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel1'); ?>/> Show
                Slope
                Labels 1% to
                2%
            </div>
            <div>
                <input id="slopeLabel2" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel2'); ?>/> Show
                Slope
                Labels
                2% to 3%
            </div>
            <div>
                <input id="slopeLabel3" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel3'); ?>/> Show
                Slope
                Labels
                above 3%
            </div>
            <div>
                <input id="slopeLabel4" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel4'); ?>/> I want
                to
                brag with my slope using 6 decimals after the comma
            </div>
            <div>
                <input id="maxSlopeLabel" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('maxSlopeLabel'); ?>/> Show
                max
                Slope
                Circle
            </div>
            <div>
                <input id="ironOverWood" type="checkbox"
                       onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('ironOverWood'); ?>/> Show
                Iron
                bridge on
                top of Wood bridge
            </div>
            <hr/>
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
                    "cf_boxcar" => "boxcar.png"
                );

                foreach (array('flatcar_logs', 'flatcar_stakes', 'flatcar_hopper', 'flatcar_cordwood', 'flatcar_tanker', 'boxcar') as $cartType) {
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
            <hr/>
            <h5>
                The settings below will require a refresh of the page
            </h5>

            <div>
                <label for="labelPrefix">Text label prefix: </label>
                <input id="labelPrefix" placeholder=".."
                       value="<?php (isset($_COOKIE['labelPrefix']) && $_COOKIE['labelPrefix'] != '') ? print($_COOKIE['labelPrefix']) : print('..'); ?>"/>
            </div>
            <button class="button" onclick="applySettings()">Apply and refresh</button>
        </div>
    </div>

    <div id="container" class="export__map">
        <svg id="demo-tiger" class="export__map-viewer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8000 8000">
            <script type="text/JavaScript">
                <![CDATA[
                function my_function(spline, segment) {
                    svg = document.getElementById('demo-tiger');
                    var children = svg.children[1].children[6].children;
                    alert('You clicked on Segment ' + spline + '-' + segment);
                    // for (var i = 0; i < children.length; i++) {
                    //     var tableChild = children[i];
                    //     // Do stuff
                    //     if(''+tableChild.getAttribute('sp') === ''+spline){
                    //         tableChild.setAttribute('stroke', 'lightblue');
                    //         console.log('.');
                    //     } else {
                    //         console.log(tableChild.getAttribute('sp'));
                    //     }
                    //     //console.log(tableChild.sp);
                    // }

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

    <div class="export__panel edit-panel">
        <div class="export__panel-scroll-content">
            <h3>Edit</h3><br/>
            <div class="edit-panel__extras">
                <h4>Rolling Stock</h4>
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

                <h4>Trees</h4>
                <form method="POST" action="/converter.php">
                    <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                    <input name="replant" value="700" size="4"> cm away from track!<br>
                    <span style="font-size: smaller">for reference:<br> 65 cm washing machine, <br> 91 cm gauge,<br> 170 cm bathtub,<br> 460 cm car,<br> 1880 cm switch<br>
                    measured to start, center and end of track-(segment) only, switches, crosses are not taken into calculation (yet)</span><br>
                    <button class="button">Replant Trees</button>
                </form>
                <br>

                <h4>Curves</h4>
                <form method="POST" action="/converter.php">
                    <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                    from: <input name="from" value="0-0" size="6"><br>
                    to: <input name="to" value="0-0" size="6"><br>
                    <button class="button">DO NOT CLICK</button>
                </form>
                <br>

                <h4>Players</h4>
                <form method="POST" action="/converter.php">
                    <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                    <table id="editPlayersTable" class="export__mapper">
                        <tr>
                            <th>Player</th>
                            <th>XP</th>
                            <th>Money</th>
                            <th>near</th>
                            <th>Delete</th>
                        </tr>
                    </table>
                    <button class="button">Apply Player Changes</button>
                </form>
                <br>

                <h4>Industries</h4>
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
                <br>

                <h4>Carts</h4>
                <form method="POST" action="/converter.php">
                    <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                    <table id="undergroundCartsTable" class="export__mapper"></table>
                    <button class="button">Get Carts from Underground</button>
                </form>
                <br/>
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
<script type="text/javascript" src="/assets/js/export.js"></script>

<?php
require_once 'utils/Minifier.php';
if (!file_exists('assets/js/mapper.min.js') || filemtime('assets/js/mapper.js') > filemtime('assets/js/mapper.min.js')) {
    $output = JShrink\Minifier::minify(file_get_contents('assets/js/mapper.js'));
    file_put_contents('assets/js/mapper.min.js', $output);
}
?>

<script type="text/javascript"
        src="/assets/js/mapper.min.js?<?php echo filemtime('assets/js/mapper.min.js'); ?>"></script>
<script type="text/javascript" src="/assets/js/js.cookie.min.js"></script>
<script type="text/javascript"
        src="/assets/js/colorshare.js?<?php echo filemtime('assets/js/colorshare.js'); ?>"></script>
<script type="text/javascript">
    const backgrounds = {
        'bg': [8000, 8000, 0, 0, 8000, 8000, 'bg.jpg'],
        'bg3': [8000, 8000, 0, 0, 8000, 8000, 'bg3.jpg'],
        'bg4': [8000, 8000, 0, 0, 8000, 8000, 'bg4.jpg'],
        'bg5': [8000, 8000, 0, 0, 8000, 8000, 'bg5.jpg']
    }
    const pattern = document.getElementsByTagName("pattern")[0];
    const image = document.getElementsByTagName("image")[0];

    const cookies = Cookies.withAttributes({
        path: '/',
        secure: true,
        expires: 30
    })

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
    }

    function toggleDisplayOptions(checkbox) {
        const element = document.getElementsByClassName(checkbox.id)[0];
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
        location.reload();
    }
</script>
</body>
</html>
<!--



                function getCurveCoordsBetweenSegments(segment1, segment2)
                {
                    // calculate formulas for segment 1 and segment 2
                    // y = m*x+n     x,y given 2 times - can be solved
                    length1 = segment1[1]['X']-segment1[0]['X'];
                    height1 = segment1[1]['Y']-segment1[0]['Y'];
                    if(length1 === 0){
                        die('Edge case 1/3 not implemented');
                    }
                    m1 = height1/length1;
                    n1 = segment1[0]['Y']-m1*segment1[0]['X'];

                    length2 = segment2[1]['X']-segment2[0]['X'];
                    height2 = segment2[1]['Y']-segment2[0]['Y'];
                    if(length2 === 0){
                        die('Edge case 2/3 not implemented');
                    }
                    m2 = height2/length2;
                    n2 = segment2[0]['Y']-m2*segment2[0]['X'];

                    // calculate intersecting point
                    // y = m1*x+n1
                    // y = m2*x+n2
                    // m1*x+n1   = m2*x+n2
                    // m1*x      = m2*x+n2-n1
                    // m1*x-m2*x = n2-n1
                    // x*(m1-m2) = n2-n1
                    // x         = (n2-n1)/(m1-m2)
                    if(m1 === m2){
                        die('Edge case 3/3 not implemented');
                    }
                    xIntersect  = (n2-n1)/(m1-m2);
                    yIntersect  = m1*xIntersect+n1;

                    // calculate half m
                    halfM = (m1+m2)/2;

                    // y = m*x+n    (given is x,y,m - what is n)
                    halfN = yIntersect-halfM*xIntersect;

                    // calculate diffs between 4 given points and the intersection - find closest point
                    distArray[1] = this->dist(segment1[0], array(xIntersect, yIntersect));
                    distArray[2] = this->dist(segment1[1], array(xIntersect, yIntersect));
                    distArray[3] = this->dist(segment2[0], array(xIntersect, yIntersect));
                    distArray[4] = this->dist(segment2[1], array(xIntersect, yIntersect));

                    ksort(distArray);
                    keys = array_keys(distArray);
                    key = array_shift(keys);

                    // calculate orthogonal through point
                    if(key === 1 || key === 2){
                        orthoM = 1/m1;
                    }
                    if(key === 2 || key === 3){
                        orthoM = 1/m2;
                    }

                    // y = m*x+n    (given is x,y,m - what is n)
                    if(key === 1){
                        orthoN = segment1[0]['Y']-orthoM*segment1[0]['X'];
                        nearest = array(segment1[0]['X'], segment1[0]['Y']);
                    }
                    if(key == 2){
                        orthoN = segment1[1]['Y']-orthoM*segment1[1]['X'];
                        nearest = array(segment1[1]['X'], segment1[1]['Y']);
                    }
                    if(key == 3){
                        orthoN = segment2[0]['Y']-orthoM*segment2[0]['X'];
                        nearest = array(segment2[0]['X'], segment2[0]['Y']);
                    }
                    if(key == 4){
                        orthoN = segment2[1]['Y']-orthoM*segment2[1]['X'];
                        nearest = array(segment2[1]['X'], segment2[1]['Y']);
                    }

                    // calculate intersection of half and ortho
                    // x         = (n2-n1)/(m1-m2)
                    xCircle  = (orthoN-halfN)/(halfM-orthoM);
                    yCircle  = halfM*xCircle+halfN;

                    // radius = distance circle and nearest point
                    radius = this->dist(array(xCircle, yCircle), nearest);

                    return array(xCircle, yCircle, radius);

                }

-->