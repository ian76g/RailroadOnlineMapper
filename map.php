<?php
require_once 'classes/ArithmeticHelper.php';
require_once 'classes/dtAbstractData.php';
require_once 'classes/dtDynamic.php';
require_once 'classes/dtHeader.php';
require_once 'classes/dtProperty.php';
require_once 'classes/dtString.php';
require_once 'classes/dtVector.php';
require_once 'classes/dtArray.php';
require_once 'classes/dtStruct.php';
require_once 'classes/dtTextProperty.php';
require_once 'classes/GVASParser.php';

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
<!DOCTYPE html >
<html lang="en">
<head>
    <meta charset="UTF8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title> Railroads Online Map </title>
    <link rel="stylesheet" href="/assets/css/reset.css"/>
    <link rel="stylesheet" href="/assets/css/main.css"/>
    <link rel="stylesheet" href="/assets/css/export.css"/>
</head>
<body class="export">
<main class="export__main">
    <nav class="export__nav">
        <button class="button" id="menu-toggle"> Menu</button>
    </nav>
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
    <div class="export__menu">###EXTRAS### <a href="<?php echo $saveFile; ?>">Download Save</a></div>
</main>

<script type="text/javascript" src="/assets/js/svg-pan-zoom.js"></script>
<script type="text/javascript" src="/assets/js/export.js"></script>
<script type="text/javascript" src="/assets/js/mapper.js"></script>
<script type="text/javascript">
    map = new Mapper(<?php echo $json; ?>);
    map.drawSVG('demo-tiger');
</script>
</body>
</html>
