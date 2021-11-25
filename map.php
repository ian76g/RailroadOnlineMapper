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

$saveFile = null;
$json = null;

if (isset($_GET['name']) && $_GET['name'] != '') {
    $saveFile = "./saves/" . $_GET['name'] . ".sav";
    if (file_exists($saveFile)) {
        $slotExtension = explode('-', substr($saveFile,0,-4));
        $slotExtension='-'.$slotExtension[sizeof($slotExtension)-1];

        $parser = new GVASParser();
        $json = $parser->parseData(file_get_contents($saveFile), false, $slotExtension);
    }
}


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
    <link rel="stylesheet" href="/assets/css/reset.css"/>
    <link rel="stylesheet" href="/assets/css/main.css"/>
    <link rel="stylesheet" href="/assets/css/export.css"/>
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
            <table id="playerTable">
                <thead>
                <tr>
                    <td>Name</td>
                    <td>XP</td>
                    <td>Money</td>
                </tr>
                </thead>
            </table>
            <hr/>
            <h3>Change background</h3>
            <div>
                <ul class="export__map--background-list">
                    <li>
                        <img id="bg" src="/assets/images/bg_90x90.png" width="90" height="90" alt="Old background"
                             onclick="changeBackground(this)">
                        <span>Old background</span>
                    </li>
                    <li>
                        <img id="bg3" src="/assets/images/bg3_90x90.png" width="90" height="90" alt="New background"
                             onclick="changeBackground(this)">
                        <span>New background</span>
                    </li>
                    <li>
                        <img id="bg4" src="/assets/images/bg4_90x90.png" width="90" height="90" alt="Psawhns background"
                             onclick="changeBackground(this)">
                        <span>Psawhns background</span>
                    </li>
                    <li>
                        <img id="bg5" src="/assets/images/bg5_90x90.png" width="90" height="90"
                             alt="Psawhns background with kanados" onclick="changeBackground(this)">
                        <span>Psawhns background with kanados overlay</span>
                    </li>
                </ul>
            </div>
            <h3>Display options</h3>
            <div>
                <input id="trees_user" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('trees_user'); ?>/> Show trees cut down by
                player
            </div>
            <div>
                <input id="trees_default" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('trees_default'); ?>/> Show trees cut down by
                default
            </div>
            <div>
                <input id="beds" type="checkbox" onclick="toggleDisplayOptions(this)"  <?php checked_if_true_or_default('beds'); ?>/> Show beds
            </div>
            <div>
                <input id="tracks" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('tracks'); ?>/> Show tracks
            </div>
            <div>
                <input id="switches" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('switches'); ?>/> Show switches and
                crossings
            </div>
            <div>
                <input id="rollingstock" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('rollingstock'); ?>/> Show rolling
                stock
            </div>
            <div>
                <input id="slopeLabel0" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel0'); ?>/> Show Slope Labels 0% to
                1%
            </div>
            <div>
                <input id="slopeLabel1" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel1'); ?>/> Show Slope Labels 1% to
                2%
            </div>
            <div>
                <input id="slopeLabel2" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel2'); ?>/> Show Slope Labels
                2% to 3%
            </div>
            <div>
                <input id="slopeLabel3" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel3'); ?>/> Show Slope Labels
                above 3%
            </div>
            <div>
                <input id="slopeLabel4" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('slopeLabel4'); ?>/> I want to brag with my slope using 6 decimals after the comma
            </div>
            <div>
                <input id="maxSlopeLabel" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('maxSlopeLabel'); ?>/> Show max Slope
                Circle
            </div>
            <div>
                <input id="drawIron" type="checkbox" onclick="toggleDisplayOptions(this)" <?php checked_if_true_or_default('ironOverWood'); ?>/> Show Iron bridge on
                top of Wood bridge
            </div>
        </div>
    </div>

    <div id="container" class="export__map">
        <svg id="demo-tiger" class="export__map-viewer" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8000 8000">
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


                <br/><br/>
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
                    </select><br />
                    <button class="button">Apply name schema</button>
                </form>

                <br/><br/>
                <form method="POST" action="/converter.php">
                    <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                    <input name="allBrakes" value="YES" type="hidden"/>
                    <button class="button">Apply all brakes</button>
                </form>


                <h4>Trees</h4>
                <form method="POST" action="/converter.php">
                    <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                    <input name="replant" value="YES" type="hidden"/>
                    <button class="button">Replant Trees</button>
                </form>


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
                <h4>Carts</h4>
                <form method="POST" action="/converter.php">
                    <input type="hidden" name="save" value="<?php echo $saveFile; ?>">
                    <table id="undergroundCartsTable" class="export__mapper"></table>
                    <button class="button">Get Carts from Underground</button>
                </form>
                <br/>
                <a class="button" href="download.php?map=<?php echo substr(basename($saveFile),0,-4); ?>">Download Save</a>
            </div>
        </div>
    </div>

    <div class="export__controls">
        <button class="button" id="zoom-out">-</button>
        <button class="button" id="zoom-in">+</button>
        <button class="button" id="reset">Reset</button>
    </div>
</main>

<script type="text/javascript" src="/assets/js/svg-pan-zoom.js.min.js"></script>
<script type="text/javascript" src="/assets/js/export.js"></script>

<?php
require_once 'utils/Minifier.php';
if(!file_exists('assets/js/mapper.min.js') || filemtime('assets/js/mapper.js') > filemtime('assets/js/mapper.min.js')) {
    $output = JShrink\Minifier::minify(file_get_contents('assets/js/mapper.js'));
    file_put_contents('assets/js/mapper.min.js', $output);
}
?>

<script type="text/javascript" src="/assets/js/mapper.min.js?<?php echo filemtime('assets/js/mapper.min.js'); ?>"></script>
<script type="text/javascript" src="/assets/js/js.cookie.min.js"></script>
<script type="text/javascript">
    const cookies = Cookies.withAttributes({
        path: '/',
        secure: true,
        expires: 30
    })

    map = new Mapper(<?php echo $json; ?>, cookies);
    map.drawSVG('demo-tiger');

    // Set different display options based on checkbox state
    const options = document.getElementsByTagName("input");
    for (const element of options) {
        if (element.type === "checkbox" && typeof element.onclick == "function") {
            toggleDisplayOptions(element);
        }
    }

    const backgrounds = {
        'bg': [8000, 8000, 0, 0, 8000, 8000, 'bg.jpg'],
        'bg3': [8000, 8000, 0, 0, 8000, 8000, 'bg3.jpg'],
        'bg4': [8000, 8000, 0, 0, 8000, 8000, 'bg4.jpg'],
        'bg5': [8000, 8000, 0, 0, 8000, 8000, 'bg5.jpg']
    }
    const pattern = document.getElementsByTagName("pattern")[0];
    const image = document.getElementsByTagName("image")[0];

    function changeBackground(clickedImage) {
        if (clickedImage.id in backgrounds) {
            pattern.setAttribute("width", backgrounds[clickedImage.id][0].toString());
            pattern.setAttribute("height", backgrounds[clickedImage.id][1].toString());
            image.setAttribute("x", backgrounds[clickedImage.id][2].toString());
            image.setAttribute("y", backgrounds[clickedImage.id][3].toString());
            image.setAttribute("width", backgrounds[clickedImage.id][4].toString());
            image.setAttribute("height", backgrounds[clickedImage.id][5].toString());
            image.setAttribute("href", "/assets/images/" + backgrounds[clickedImage.id][6]);
        }
    }

    function toggleDisplayOptions(checkbox) {
        if (checkbox.id === "drawIron") {
            if (checkbox.checked) {
                cookies.set('ironOverWood', true);
            } else {
                cookies.set('ironOverWood', false);
            }
        }

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
</script>
</body>
</html>