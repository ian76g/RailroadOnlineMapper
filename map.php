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

$saveFile = null;
$json = null;

if (isset($_GET['name']) && $_GET['name'] != '') {
    $saveFile = "./saves/" . $_GET['name'] . ".sav";
    if (file_exists($saveFile)) {
        $parser = new GVASParser();
        $parser->NEWUPLOADEDFILE = $saveFile;
        $json = $parser->parseData(file_get_contents($saveFile), false);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Railroads Online Map</title>
    <link rel="stylesheet" href="/assets/css/reset.css" />
    <link rel="stylesheet" href="/assets/css/main.css" />
    <link rel="stylesheet" href="/assets/css/export.css" />
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
                ###EXTRAS###
                <a href="<?php echo $saveFile; ?>">Download Save</a>
            </div>
        </div>
    </div>

    <div class="export__controls">
        <button class="button" id="zoom-out">-</button>
        <button class="button" id="zoom-in">+</button>
        <button class="button" id="reset">Reset</button>
    </div>
</main>

<script type="text/javascript" src="/assets/js/svg-pan-zoom.js"></script>
<script type="text/javascript" src="/assets/js/export.js"></script>
<script type="text/javascript" src="/assets/js/mapper.js"></script>
<script type="text/javascript">
    map = new Mapper(<?php echo $json; ?>);
    map.drawSVG('demo-tiger');
    map.populatePlayerTable();
</script>
</body>
</html>