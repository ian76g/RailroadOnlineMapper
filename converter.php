<?php
ini_set('memory_limit', -1);
set_time_limit(50);
$v = 46;
echo "\n".'running converter version 0.'.$v."\n";

/**
 * define some stuff we need later
 */
$start = microtime(true);
$imagewidth = 8000; // desired images width (x,y) aproximately
//set the path to find the save games.... last line wins
$path = 'uploads';
$fontFile = 'OpenSans-Regular.ttf';

if (isset($argv[1]) && !empty($argv[1])) {
    $path = pathinfo($argv[1])['dirname'];
}
$empty = false;
if (isset($_POST['empty']) && $_POST['empty']) {
    $empty = true;
}

$bg = 'bg5';
if (isset($_POST['background'])) {
    if ($_POST['background'] == 'bg') {
        $bg = 'bg';
    }
    if ($_POST['background'] == 'bg3') {
        $bg = 'bg3';
    }
    if ($_POST['background'] == 'bg4') {
        $bg = 'bg4';
    }
    if ($_POST['background'] == 'bg5') {
        $bg = 'bg5';
    }
}
$bgOffsets = array(
    'bg5' => array(9400, 9600, -720, -770, 9400, 9600),
    'bg4' => array(9400, 9600, -720, -770, 9400, 9600),
    'bg3' => array(8000, 8000, 0, 50, 8000, 8000),
    'bg' => array(8000, 8000, 0, 0, 8000, 8000),
);
//print_r($bgOffsets[$bg]);
// devine the SVG structure
$htmlSvg = '<!DOCTYPE html>
<html>
  <head>
    <script src="svg-pan-zoom.js"></script>
    <style>.myStuff {font-family: Verdana; font-size: 8pt;}</style>
  </head>
  <body>
    <div id="container" style="width: 850px; height: 850px; border:1px solid black; float:left">
      <svg id="demo-tiger" xmlns="http://www.w3.org/2000/svg" style="display: inline; width: inherit; min-width: inherit; max-width: inherit; height: inherit; min-height: inherit; max-height: inherit; " viewBox="0 0 8000 8000" version="1.1">
    <defs>
    <pattern id="bild" x="0" y="0" width="' . $bgOffsets[$bg][0] . '" height="' . $bgOffsets[$bg][1] . '" patternUnits="userSpaceOnUse">
      <image x="' .
    ((isset($_POST['xoff']) && $_POST['xoff'] !== '') ? ($_POST['xoff']) : $bgOffsets[$bg][2]) . '" y="' .
    ((isset($_POST['yoff']) && $_POST['xoff'] !== '') ? ($_POST['yoff']) : $bgOffsets[$bg][3]) . '" width="' .
    ((isset($_POST['xsoff']) && $_POST['xoff'] !== '') ? ($_POST['xsoff']) : $bgOffsets[$bg][4]) . '" height="' .
    ((isset($_POST['ysoff']) && $_POST['xoff'] !== '') ? ($_POST['ysoff']) : $bgOffsets[$bg][5]) . '" href="' . $bg . '.png" />
    </pattern>
  </defs>
  <rect x="0" y="0" width="8000" height="8000" fill="url(#bild)" stroke="black"/>  
    ###SVG###
';
$htmlSvg .= <<<EOF
</svg>
    <button id="enable">enable</button>
    <button id="disable">disable</button>
    </div>
    <div style="float:left" class="myStuff">###EXTRAS###</div>


    <script>
      // Don't use window.onLoad like this in production, because it can only listen to one function.
      window.onload = function() {
        // Expose to window namespase for testing purposes
        window.zoomTiger = svgPanZoom('#demo-tiger', {
          zoomEnabled: true,
          controlIconsEnabled: true,
          fit: true,
          center: true,
          // viewportSelector: document.getElementById('demo-tiger').querySelector('#g4') // this option will make library to misbehave. Viewport should have no transform attribute
        });

        document.getElementById('enable').addEventListener('click', function() {
          window.zoomTiger.enableControlIcons();
        })
        document.getElementById('disable').addEventListener('click', function() {
          window.zoomTiger.disableControlIcons();
        })
      };
    </script>

  </body>

</html>
EOF;

$dh = opendir($path);

/**
 * read all save files
 */
$files = array();
while ($file = readdir($dh)) {
    if (substr($file, -4) == '.sav') {
        $mtime = filemtime($path . '/' . $file);
        $files[$mtime] = $file;
    }
}

/**
 * find most recent save
 */
krsort($files);

if (!isset($NEWUPLOADEDFILE)) {
    if (isset($argv[1])) {
        $NEWUPLOADEDFILE = $argv[1];
    } else {
        die('no file submitted' . "\n");
    }
}

$files = array($NEWUPLOADEDFILE);


/**
 * /*
 * do all files that need to be done
 */
foreach ($files as $file) {
    $jpegFileName = str_replace('.sav', '', basename($file)) . '.jpeg';
    $htmlFileName = str_replace('.sav', '', basename($file)) . '.html';

    $doSvg = true;
    $doJpg = false;
//    if (file_exists($htmlFileName)) $doSvg = false;
//    if (file_exists($jpegFileName)) $doJpg = false;

    if (!$doSvg && !$doJpg) continue;

    $svg = '';
    $savegame = $path . "/" . $file;

    $myParser = new GVASParser();
    /**
     * read whole file into memory
     */
    $data = json_decode($myParser->parseData(file_get_contents($path . '/' . $file)), true);

    /**
     * find min and max X and Y values inthe save
     * whoever built track built it "somewhere"....
     */
    $minX = 0;
    $maxX = 0;

    $minY = 0;
    $maxY = 0;

    $types = array();

    /**
     * since the 0,0 of the map is not like in an image the top left corner, we have to normalize the coordinates
     */
    $minX = -200000;
    $maxX = 200000;
    $minY = -200000;
    $maxY = 200000;

    $x = $maxX - $minX;
    $y = $maxY - $minY;

//echo "X: $x, Y: $y\n";

    /**
     * Now we need a factor to scale the ingame coordinates of the network to our 8000px image
     */

    $max = max($x, $y);
    $scale = ($imagewidth * 100 / $max);
//echo "scale $scale\n";
// : 100
    $switchRadius = (80 / 2.2107077) * $scale;   // (size an armlength of switches)
    $engineRadius = 6 * $scale;                // (radius of locomotives and carts)

    $turnTableRadius = (10 / 2.2107077) * $scale;

    $imx = (int)$x / 100 * $scale;
    $imy = (int)$y / 100 * $scale;

//echo "[$imx][$imy]\n";

    /**
     * create an empty image and define some colors, paint the canvas white
     */
    if ($doJpg) {
        $img = imagecreate($imx, $imy);
        $white = imagecolorallocate($img, 208, 240, 200);
        $colorBanks = imagecolorallocate($img, 163, 163, 143);
        $colorBridgeWooden = imagecolorallocate($img, 224, 224, 82);
        $colorBridgeSteel = imagecolorallocate($img, 148, 148, 163);
        $colorTrack = imagecolorallocate($img, 0, 0, 0);
        $colorSwitchActive = imagecolorallocate($img, 255, 0, 0);
        $colorSwitchInactive = imagecolorallocate($img, 255, 155, 155);
        $colorGreen = imagecolorallocate($img, 50, 255, 50);
        imagefill($img, 1, 1, $white);

        //assumption 400.000 pixel ingame
        $bg = imagecreatefrompng('bg.png');
        $bgPixel = imagesx($bg);

        imagecopyresampled($img, $bg, 0, 0, 0, 0, $imagewidth, $imagewidth, $bgPixel, $bgPixel);

//        $rs = imagecreatefrompng('rollingstock.png');
//        imagecopyresampled($img, $rs, $imagewidth - 3 * imagesx($rs), 0, 0, 0, 3 * imagesx($rs), 3 * imagesy($rs), imagesx($rs), imagesy($rs));

    } else {
        $img = imagecreate(10, 10); //dummy
        $colorBanks = imagecolorallocate($img, 163, 163, 143);
        $colorBridgeWooden = imagecolorallocate($img, 224, 224, 82);
        $colorBridgeSteel = imagecolorallocate($img, 148, 148, 163);
        $colorTrack = imagecolorallocate($img, 0, 0, 0);
        $colorSwitchActive = imagecolorallocate($img, 255, 0, 0);
        $colorSwitchInactive = imagecolorallocate($img, 255, 155, 155);
        $colorGreen = imagecolorallocate($img, 50, 255, 50);
    }

    /**
     * set some basic order on what to draw first, rails of course should be painted last
     *
     * some info in the JSON is wrong - issue on github is created
     */

    $order = array(
        '1' => array(15, $colorBanks, 'darkkhaki'), // variable bank
        '2' => array(15, $colorBanks, 'darkkhaki'),  //  constant bank
        '5' => array(15, $colorBanks, 'darkgrey'), // variable wall
        '6' => array(15, $colorBanks, 'darkgrey'),   // constant wall
        '7' => array(15, $colorBridgeSteel, 'lightblue'),   //  iron bridge
        '3' => array(15, $colorBridgeWooden, 'orange'),  //  wooden bridge
        '4' => array(3, $colorTrack, 'black'), // trendle track
        '0' => array(3, $colorTrack, 'black'),  // track  darkkhaki, darkgrey,orange,blue,black
    );

    $totalTrackLength = 0;
    $totalSwitches = 0;
    $totalLocos = 0;
    $totalCarts = 0;
    $maxSlope = 0;
    /**
     * Loop the order array painting one type over the next
     */
    foreach ($order as $current => $optionsArr) {
        if ($doJpg) imagesetthickness($img, $optionsArr[0]);
        foreach ($data['Splines'] as $spline) {
            $type = $spline['Type'];
            @$types[$type]++;
            if ($type != $current) continue;            // if this spline is not the current type, skip it
            $segments = $spline['Segments'];
            foreach ($segments as $segment) {
                if ($segment['Visible'] != 1) continue; // skip invisible tracks
                // draw the line
                if ($doJpg) {
                    imageline($img,
                        $imx - (int)(($segment['LocationStart']['X'] - $minX) / 100 * $scale), $imy - (int)(($segment['LocationStart']['Y'] - $minY) / 100 * $scale),
                        $imx - (int)(($segment['LocationEnd']['X'] - $minX) / 100 * $scale), $imy - (int)(($segment['LocationEnd']['Y'] - $minY) / 100 * $scale),
                        $optionsArr[1]
                    );
                }

                if ($doSvg) {
                    $svg .= '<line x1="' .
                        ($imx - (int)(($segment['LocationStart']['X'] - $minX) / 100 * $scale)) . '" y1="' .
                        ($imy - (int)(($segment['LocationStart']['Y'] - $minY) / 100 * $scale))
                        . '" x2="' . ($imx - (int)(($segment['LocationEnd']['X'] - $minX) / 100 * $scale)) . '" y2="' .
                        ($imy - (int)(($segment['LocationEnd']['Y'] - $minY) / 100 * $scale))
                        . '" stroke="' . $optionsArr[2] . '" stroke-width="' . $optionsArr[0] . '"/>' . "\n";
                }


                $distance = sqrt(
                    pow($segment['LocationEnd']['X'] - $segment['LocationStart']['X'], 2) +
                    pow($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y'], 2) +
                    pow($segment['LocationEnd']['Z'] - $segment['LocationStart']['Z'], 2)
                );

                if (in_array($type, array(4, 0))) {
                    $totalTrackLength += $distance;

                    $height = $segment['LocationEnd']['Z'] - $segment['LocationStart']['Z'];
                    $height = abs($height);
                    $length = sqrt(pow($segment['LocationEnd']['X'] - $segment['LocationStart']['X'], 2) +
                        pow($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y'], 2));
                    $maxSlope = max($maxSlope,
                        ($height * 100 / $length));
                }
                if ($distance < 100) {
                    if ($doJpg) {
//                        imageellipse($img,
//                            $imx - (int)(($segment['LocationStart']['X'] - $minX) / 100 * $scale),
//                            $imy - (int)(($segment['LocationStart']['Y'] - $minY) / 100 * $scale),
//                            20, 20, $colorSwitchActive);
                    }
                    @$distances[$current . '_' . $distance]++;
                }

                if (false && $distance > 0 && in_array($type, array(4, 0))) {
                    $slope = asin(($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y']) / $distance) / pi() * 180;
                    if ($slope < -2 || $slope > 2) {
                        if ($doJpg) {
                            imagettftext($img, 10, 0, ($imx - (int)(($segment['LocationStart']['X'] - $minX) / 100 * $scale)) + 10,
                                ($imy - (int)(($segment['LocationStart']['Y'] - $minY) / 100 * $scale)) + 10, $colorTrack, $fontFile,
                                round($slope, 1));
                        }
                    }
                }
            }
        }
    }
//print_r($types);
//        exit;
    /**
     * Fill in the missing gaps AKA switches
     */
    $types = array();
    foreach ($data['Switchs'] as $switch) {
        $dir = false;
        $type = trim($switch['Type']);
        @$types[$type]++;
        /**
         * 0 = SwitchLeft           = lever left switch going left
         * 1 = SwitchRight          = lever right switch going right
         * 2 =                      = Y
         * 3 =                      = Y mirror
         * 4 = SwitchRightMirror    = lever left switch going right
         * 5 = SwitchLeftMirror     = lever right switch going left
         * 6 = SwitchCross90        = cross
         */
        $state = $switch['Side'];

        switch ($type) {
            case 0 :
                $dir = -7;
                $state = !$state;
                break;
            case 1 :
                $dir = 7;
                break;
            case 2 :
                $dir = -7;
                break;
            case 3 :
                $dir = 7;
                break;
            case 4 :
                $dir = 7;
                break;
            case 5 :
                $state = !$state;
                $dir = -7;
                break;
            case 6 :
                $dir = '99';
                break;
            default:
                $dir = 1;
        }

        if (!$dir) {
            var_dump($type);
            die('WHOOPS');
        }
        $totalSwitches += 1;
        $segments = $switch['Location'];
        // fix given angles and convert to radiant - subtract 90 - because ingame coordinates do not point NORTH (!?)
        $rotation = deg2rad($switch['Rotation'][1] - 90);
        $rotSide = deg2rad($switch['Rotation'][1] - 90 + $dir);
        $rotCross = deg2rad($switch['Rotation'][1]+180);

// circle the switch - used to debug angles and sizes
//            imageellipse($img,
//        $imx - (int)(($switch['Location']['X'] - $minX) /100* $scale), $imy - (int)(($switch['Location']['Y'] - $minY) /100* $scale),
//        80, 80, $colorSwitchInactive);


        // straight MAIN
        if ($doJpg) {
            $active = $colorTrack;
            $inactive = $colorSwitchActive;
            imageline($img,
                $imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale), $imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale),
                $imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotation) * $switchRadius / 2),
                $imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotation) * $switchRadius / 2),
                $state ? $active : $inactive
            );
            // curve SIDE
            imageline($img,
                $imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale), $imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale),
                $imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotSide) * $switchRadius / 2),
                $imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotSide) * $switchRadius / 2),
                $state ? $inactive : $active
            );
        }

        if ($doSvg) {
            if ($dir == 99) { //CROSS
                $crosslength = $switchRadius / 10;

                $x = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale));
                $y = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale));
                $x2 = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotCross) * $crosslength));
                $y2 = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotCross) * $crosslength));

                $cx = $x + ($x2 - $x) / 2;
                $cy = $y + ($y2 - $y) / 2;


                $svg .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="black" stroke-width="3"/>' . "\n";
                $svg .= '<line x1="' .
                    ($cx - (cos($rotation) * $crosslength)) .
                    '" y1="' .
                    ($cy - (sin($rotation) * $crosslength)) .
                    '" x2="' . ($cx + (cos($rotation) * $crosslength)) .
                    '" y2="' . ($cy + (sin($rotation) * $crosslength)) .
                    '" stroke="black" stroke-width="3"/>' . "\n";

            } else {
                $x = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale));
                $y = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale));
                $xStraight = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotation) * $switchRadius / 2));
                $yStraight = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotation) * $switchRadius / 2));
                $xSide = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotSide) * $switchRadius / 2));
                $ySide = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotSide) * $switchRadius / 2));

                if ($state) {
//                    $svg .= '<text x="' . $x . '" y="' . $y . '">   ' . $type . '/' . $state . '</text>';
                    $svg .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . $xStraight . '" y2="' . $yStraight . '" stroke="red" stroke-width="3"/>' . "\n";
                    $svg .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . $xSide . '" y2="' .$ySide. '" stroke="black" stroke-width="3"/>' . "\n";
                } else {
//                    $svg .= '<text x="' . $x . '" y="' . $y . '">   ' . $type . '/' . $state . '</text>';
                    $svg .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . $xSide . '" y2="' . $ySide . '" stroke="red" stroke-width="3"/>' . "\n";
                    $svg .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . $xStraight . '" y2="' . $yStraight . '" stroke="black" stroke-width="3"/>' . "\n";

                }
            }
        }


// debugging
//            imagettftext($img, 20, 0,
//        $imx - (int)(($switch['Location']['X'] - $minX) /100* $scale), $imy - (int)(($switch['Location']['Y'] - $minY) /100* $scale),
//    $colorTrack, $fontFile, $type);

    }


    /**
     * Fill in the missing gaps AKA turntables
     */
    if (!isset($data['Turntables'])) {
        $data['Turntables'] = array();
    }
    foreach ($data['Turntables'] as $table) {
        $type = trim($table['Type']);
        /**
         * 0 = regular
         * 1 = light and nice
         */
        @$totalTurntables += 1;
        // fix given angles and convert to radiant - subtract 90 - because ingame coordinates do not point NORTH (!?)
        $rotation = deg2rad($table['Rotator'][1] + 90);
        $rotation2 = deg2rad($table['Rotator'][1] + 90 - $table['Deck'][1]);

        if ($doJpg) {
        }

        if ($doSvg) {
            $turnTableRadius = 25;

            $x = ($imx - (int)(($table['Location'][0] - $minX) / 100 * $scale));
            $y = ($imx - (int)(($table['Location'][1] - $minX) / 100 * $scale));
            $x2 = ($imx - (int)(($table['Location'][0] - $minX) / 100 * $scale) + (cos($rotation) * $turnTableRadius));
            $y2 = ($imy - (int)(($table['Location'][1] - $minY) / 100 * $scale) + (sin($rotation) * $turnTableRadius));

            $cx = $x + ($x2 - $x) / 2;
            $cy = $y + ($y2 - $y) / 2;

            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . ($turnTableRadius / 2) . '" stroke="black" stroke-width="1" fill="lightyellow" />' . "\n";

            $svg .= '<line x1="' . ($cx - (cos($rotation2) * $turnTableRadius / 2)) .
                '" y1="' . ($cy - (sin($rotation2) * $turnTableRadius / 2))
                . '" x2="' . ($cx + (cos($rotation2) * $turnTableRadius / 2)) .
                '" y2="' . ($cy + (sin($rotation2) * $turnTableRadius / 2))
                . '" stroke="black" stroke-width="3"/>' . "\n";

        }
    }


    //print_r($types);
    /**
     * draw some vehicles ontop of that image
     * define size and color of vehicle here
     */

    $cartColors = array(
        'handcar' => array($engineRadius, imagecolorallocate($img, 200, 200, 200), 'black'),
        'porter_040' => array($engineRadius, imagecolorallocate($img, 224, 224, 82), 'black'),
        'porter_042' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'eureka' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'eureka_tender' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'climax' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'heisler' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'class70' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'class70_tender' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'cooke260' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'cooke260_tender' => array($engineRadius, imagecolorallocate($img, 30, 30, 30), 'black'),
        'flatcar_logs' => array($engineRadius / 3, imagecolorallocate($img, 224, 50, 224), 'red'),
        'flatcar_cordwood' => array($engineRadius / 3 * 2, imagecolorallocate($img, 224, 50, 50), 'orange'),
        'flatcar_stakes' => array($engineRadius / 3 * 2, imagecolorallocate($img, 224, 224, 50), 'yellow'),
        'flatcar_hopper' => array($engineRadius / 3 * 2, imagecolorallocate($img, 20, 20, 20), 'brown'),
        'flatcar_tanker' => array($engineRadius / 3 * 2, imagecolorallocate($img, 100, 100, 100), 'grey'),
    );

    imagesetthickness($img, 1);
    $cartExtraStr = '<table class="myStuff"><tr><th>Type</th><th>Name</th><th>Number</th><th>near</th></tr>###TROWS###</table>';
    $trows = '';
    $trow = '<tr><td>###1###</td><td>###2###</td><td align="right">###3###</td><td>###4###</td></tr>';
    foreach ($data['Frames'] as $vehicle) {
        $exArr = array($vehicle['Type'], strtoupper(strip_tags($vehicle['Name'])), $vehicle['Number']);
        if ($empty || strip_tags($vehicle['Name']) || $vehicle['Number']) {
            $exArr[] = nearestIndustry($vehicle['Location']);
            $trows .= str_replace(array('###1###', '###2###', '###3###', '###4###'), $exArr, $trow);

        }

        if ($doJpg) {
            rotatedellipse($img,
                $imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale), $imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale),
                $engineRadius * 1.1, ($engineRadius * 1.1) / 2, $vehicle['Rotation'][1], $cartColors[$vehicle['Type']][1], true);
            // draw the outline (black)
            rotatedellipse($img,
                $imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale), $imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale),
                $engineRadius * 1.1, ($engineRadius * 1.1) / 2, $vehicle['Rotation'][1], $colorTrack, false);
//            if($vehicle['Type'] == 'flatcar_hopper'){
//                imagettftext($img, $engineRadius / 4 * 3, 0,
//                    $imx - (int)(($vehicle['Location']['X'] - $minX) / 100 * $scale), $imy - (int)(($vehicle['Location']['Y'] - $minY) / 100 * $scale),
//                    $colorTrack, $fontFile, '  ' . $vehicle['Location']['X'].'/'.$vehicle['Location']['Y'].'/'.$vehicle['Location']['Z']);
//            }
        }

        if ($doSvg) {
            $svg .= '<ellipse cx="' . ($imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale)) .
                '" cy="' . ($imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale)) . '" rx="' . ($engineRadius / 2) .
                '" ry="' . ($engineRadius / 3) .
                '" style="fill:' . $cartColors[$vehicle['Type']][2] . ';stroke:black;stroke-width:1" transform="rotate(' . $vehicle['Rotation'][1] .
                ', ' . ($imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale)) . ', ' . ($imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale)) . ')"
              />';
        }

        // add some names to the locomotives
        if ($vehicle['Type'] == 'porter_040'
            || $vehicle['Type'] == 'porter_042'
            || $vehicle['Type'] == 'eureka'
            || $vehicle['Type'] == 'climax'
            || $vehicle['Type'] == 'heisler'
            || $vehicle['Type'] == 'class70'
            || $vehicle['Type'] == 'cooke260'
        ) {
            $totalLocos++;
            $name = strtoupper(strip_tags($vehicle['Name']));
            // fallback to engine type when no name was given
            if (!$name) {
                $name = ucfirst($vehicle['Type']);
            }
            //$name.=' ('.$vehicle['Location']['Z'].')';
            // label locomotives
            if ($doJpg) {
                imagettftext($img, $engineRadius / 4 * 3, 0,
                    $imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale), $imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale),
                    $colorTrack, $fontFile, '  ' . $name);
            }
            if ($doSvg) {
                $svg .= '<text x="' . ($imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale)) .
                    '" y="' . ($imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale)) . '" >' . '&nbsp;&nbsp;' . $name . '</text>' . "\n";

            }
        } else {
            $totalCarts++;
        }

    }

    $cartExtraStr = str_replace('###TROWS###', $trows, $cartExtraStr);
    $htmlSvg = str_replace('###EXTRAS###', $cartExtraStr, $htmlSvg);


    $types = array();
    /**
     * add the industries to the map
     */
    foreach ($data['Industries'] as $site) {

        @$types[$site['Type']]++;
        /**
         * again - fix some issues with wrong INPUT from json
         */
        $xoff = $yoff = 0;
        switch ($site['Type']) {
            case '1':
                $name = 'Logging Camp';
                $rotation = 0;
                $xoff = -70;
                $yoff = -30;
                break;
            case '2':
                $name = 'Sawmill';
                array_pop($site['EductsStored']);
                array_pop($site['EductsStored']);
                array_pop($site['EductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                $xoff = -35;
                $yoff = -15;
                $rotation = 45;
                break;
            case '3':
                $name = 'Smelter';
                array_pop($site['EductsStored']);
                array_pop($site['EductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                $rotation = 90;
                break;
            case '4':
                $name = 'Ironworks';
                array_pop($site['EductsStored']);
                array_pop($site['EductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                $rotation = 90;
                break;
            case '5':
                $name = 'Oilfield';
                array_pop($site['EductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                $rotation = 0;
                break;
            case '6':
                $name = 'Refinery';
                array_pop($site['EductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                $rotation = 0;
                break;
            case '7':
                $name = 'Coal Mine';
                array_pop($site['EductsStored']);
                array_pop($site['EductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                $rotation = -20;
                $xoff = -20;
                $yoff = 20;
                break;
            case '8':
                $name = 'Iron Mine';
                array_pop($site['EductsStored']);
                array_pop($site['EductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                array_pop($site['ProductsStored']);
                $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                $rotation = 45;
                $yoff = +50;
                $xoff = -20;
                break;
            case '9':
                $name = 'Freight Depot';
                $rotation = 90;
                break;
            case '10':
                $name = 'F';
//                $name .="\nI:".implode(',', $site['EductsStored'])."\nO:".implode(',', $site['ProductsStored']);
                $rotation = 0;
                break;
        }


        @$db = unserialize(@file_get_contents('db.db'));
        $db[$NEWUPLOADEDFILE] = array($totalTrackLength, $totalSwitches, $totalLocos, $totalCarts, $maxSlope);
        @file_put_contents('db.db', serialize($db));
        // label the industries
        if ($doJpg) {
            imagettftext($img, 20, $rotation,
                $imx - (int)(($site['Location'][0] - $minX) / 100 * $scale) + $xoff, $imy - (int)(($site['Location'][1] - $minY) / 100 * $scale) + $yoff,
                $colorTrack, $fontFile, '' . strip_tags($name));
        }

        if ($doSvg) {
            $svg .= '<text x="' . ($imx - (int)(($site['Location'][0] - $minX) / 100 * $scale) + $xoff) .
                '" y="' . ($imy - (int)(($site['Location'][1] - $minY) / 100 * $scale) + $yoff) . '" transform="rotate(' . $rotation .
                ',' . ($imx - (int)(($site['Location'][0] - $minX) / 100 * $scale) + $xoff) .
                ', ' . ($imy - (int)(($site['Location'][1] - $minY) / 100 * $scale) + $yoff) . ')" >' . $name . '</text>' . "\n";
        }

    }

    /**
     * add Watertowers to the map
     */
    foreach ($data['Watertowers'] as $site) {
        $x = $imx - (int)(($site['Location'][0] - $minX) / 100 * $scale);
        $y = $imy - (int)(($site['Location'][1] - $minY) / 100 * $scale);
        if ($doJpg) {
            imagettftext($img, 20, 0, $x, $y, $colorTrack, $fontFile, 'W');
        }
        if ($doSvg) {
            $svg .= '<text x="' . ($x) . '" y="' . ($y) . '" >W</text>' . "\n";
        }
    }

    /**
     * add player info to the map
     */

    $text = '===== PLAYERS ON THIS SERVER =====' . "\n\n\n";
    $text2 = '.' . "\n\n\n";
    if ($data['Players'][0]['Name'] == 'ian76g') {
        $data['Players'][0]['Money'] -= 30000;
    }
    foreach ($data['Players'] as $player) {
        $text .= str_pad($player['Name'], 20, ' ', STR_PAD_BOTH) . "\n\n";
        $text2 .= ' (XP ' . str_pad($player['Xp'], 7, ' ', STR_PAD_RIGHT) . '  ' .
            str_pad($player['Money'], 8, ' ', STR_PAD_LEFT) . '$)' . "\n\n";
    }
    if ($doJpg) {
        imagettftext($img, 20, 0,
            50, 50,
            $colorTrack, $fontFile, $text);
        imagettftext($img, 20, 0,
            350, 50,
            $colorTrack, $fontFile, $text2);
    }
    if ($doSvg) {
        $svg .= '<text x="50" y="50" font-size="20" dy="0">';
        $textlines = explode("\n", $text);
        foreach ($textlines as $textline) {
            $svg .= '<tspan x="50" dy="1.2em">' . $textline . '&nbsp;</tspan>';
        }
        $svg .= '</text>' . "\n";

        $svg .= '<text x="350" y="50" font-size="20" dy="0">';
        $textlines = explode("\n", $text2);
        foreach ($textlines as $textline) {
            $svg .= '<tspan x="350" dy="1.2em">' . $textline . '&nbsp;</tspan>';
        }
        $svg .= '</text>' . "\n";
    }


    /**
     * chart
     */
    $carts = array(
        'flatcar_logs' => array($engineRadius / 3, imagecolorallocate($img, 224, 50, 224), 'red'),
        'flatcar_cordwood' => array($engineRadius / 3 * 2, imagecolorallocate($img, 224, 50, 50), 'orange'),
        'flatcar_stakes' => array($engineRadius / 3 * 2, imagecolorallocate($img, 224, 224, 50), 'yellow'),
        'flatcar_hopper' => array($engineRadius / 3 * 2, imagecolorallocate($img, 20, 20, 20), 'brown'),
        'flatcar_tanker' => array($engineRadius / 3 * 2, imagecolorallocate($img, 100, 100, 100), 'grey'),
    );

    if ($doJpg) imagesetthickness($img, 1);
    $cartsDrawn = 0;
    foreach ($carts as $cartType => $cart) {
        $x = $imagewidth / 4;
        $y = 100 + $cartsDrawn * 5 * ($engineRadius * 1.1) / 2;
        $rx = $engineRadius * 1.1 * 2;
        $ry = ($engineRadius * 1.1);
        if ($doJpg) {
            rotatedellipse($img, $x, $y, $rx, $ry, 0, $carts[$cartType][1], true);
            // draw the outline (black)
            rotatedellipse($img, $x, $y, $rx, $ry, 0, $colorTrack, false);
            imagettftext($img, 3 * ($engineRadius * 1.1) / 2, 0,
                $x + $rx, $y, $colorTrack, $fontFile, $cartType);
        }
        if ($doSvg) {
            $svg .= '<ellipse cx="' . $x .
                '" cy="' . $y .
                '" rx="' . $rx .
                '" ry="' . $ry .
                '" style="fill:' . $cart[2] . ';stroke:black;stroke-width:1" />' . "\n";
        }
        $cartsDrawn++;


    }
    if ($doSvg) {
        $svg .= '<text x="' . ($x + $rx) . '" y="' . (100 - 2 * $ry) . '" font-size="30" dy="0">';
        foreach ($carts as $cartType => $cart) {
            $svg .= '<tspan x="' . ($x + $rx) . '" dy="1.2em">' . $cartType . '&nbsp;</tspan>';
        }
        $svg .= '</text>' . "\n";
    }


//output the image
//    header('Content-Type: image/jpeg');

    if ($doJpg) {
        imagejpeg($img, 'done/' . $jpegFileName, 75);
        $cmd = '"c:\Program Files (x86)\WinSCP\WinSCP.com" /command "open ftp://user:password@server.de/" "put ' . $jpegFileName . ' /html/minizwerg/" "exit"';
//        passthru($cmd);
    }
    //@print_r($distances);
    if ($doSvg) {
        file_put_contents('done/' . $htmlFileName, str_replace('&nbsp;', ' ', str_replace('###SVG###', $svg, $htmlSvg)));
        $cmd = '"c:\Program Files (x86)\WinSCP\WinSCP.com" /command "open ftp://user:password@server.de/" "put ' . $htmlFileName . ' /html/minizwerg/" "exit"';
//        passthru($cmd);
    }

//    if (isset($argv[1])) {
//        @unlink($jpegFileName);
//        @unlink($htmlFileName);
//    }
    echo "redered in " . (microtime(true) - $start) . " microseconds\n";
    unlink('uploads/' . $NEWUPLOADEDFILE);
}
//debug
// print_r($types);


function nearestIndustry($coords)
{
    $minDist = 800000;
    global $data;
    foreach ($data['Industries'] as $i) {
        if ($i['Type'] < 10) {
            $d = dist($i['Location'], $coords);
            if ($d < $minDist) {
                $minDist = $d;
                $ind = $i['Type'];
            }
        }
    }

    switch ($ind) {
        case '1':
            $name = 'Logging Camp';
            break;
        case '2':
            $name = 'Sawmill';
            break;
        case '3':
            $name = 'Smelter';
            break;
        case '4':
            $name = 'Ironworks';
            break;
        case '5':
            $name = 'Oilfield';
            break;
        case '6':
            $name = 'Refinery';
            break;
        case '7':
            $name = 'Coal Mine';
            break;
        case '8':
            $name = 'Iron Mine';
            break;
        case '9':
            $name = 'Freight Depot';
            break;
    }

    return $name;
}

function dist($coords, $coords2)
{
    $distance = sqrt(
        pow($coords[0] - $coords2[0], 2) +
        pow($coords[1] - $coords2[1], 2) +
        pow($coords[2] - $coords2[2], 2)
    );

    return $distance;
}


function rotatedellipse($im, $cx, $cy, $width, $height, $rotateangle, $colour, $filled = false)
{
    // modified here from nojer's version
    // Rotates from the three o-clock position clockwise with increasing angle.
    // Arguments are compatible with imageellipse.

//    imageroundedrectangle($im, $cx-$width/2, $cy-$height/2, $cx+$width/2, $cy+$height/2, 2, $colour, $rotateangle);
//    return;


    $width = $width / 2;
    $height = $height / 2;

    // This affects how coarse the ellipse is drawn.
    $step = 3;

    $cosangle = cos(deg2rad($rotateangle));
    $sinangle = sin(deg2rad($rotateangle));

    // $px and $py are initialised to values corresponding to $angle=0.
    $px = $width * $cosangle;
    $py = $width * $sinangle;

    for ($angle = $step; $angle <= (180 + $step); $angle += $step) {

        $ox = $width * cos(deg2rad($angle));
        $oy = $height * sin(deg2rad($angle));

        $x = ($ox * $cosangle) - ($oy * $sinangle);
        $y = ($ox * $sinangle) + ($oy * $cosangle);

        if ($filled) {
            triangle($im, $cx, $cy, $cx + $px, $cy + $py, $cx + $x, $cy + $y, $colour);
            triangle($im, $cx, $cy, $cx - $px, $cy - $py, $cx - $x, $cy - $y, $colour);
        } else {
            imageline($im, $cx + $px, $cy + $py, $cx + $x, $cy + $y, $colour);
            imageline($im, $cx - $px, $cy - $py, $cx - $x, $cy - $y, $colour);
        }
        $px = $x;
        $py = $y;
    }
}

function triangle($im, $x1, $y1, $x2, $y2, $x3, $y3, $colour)
{
    $coords = array($x1, $y1, $x2, $y2, $x3, $y3);
    imagefilledpolygon($im, $coords, 3, $colour);
}

function imageroundedrectangle(&$img, $x1, $y1, $x2, $y2, $r, $color, $angle)
{

    $centerX = $x2 - $x1;
    $centerY = $y2 - $y1;
    $res = getBoundingBox($angle, $centerX, $centerY);
    $rDiffX = $centerX - $res[0];
    $rDiffY = $centerY - $res[1];
    $x1 += $rDiffX / 2;
    $x2 -= $rDiffX / 2;
    $y1 += $rDiffY / 2;
    $y2 -= $rDiffY / 2;

    $r = min($r, floor(min(($x2 - $x1) / 2, ($y2 - $y1) / 2)));
    // render corners
    imagefilledarc($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 0, 360, $color, IMG_ARC_PIE);
    imagefilledarc($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 0, 360, $color, IMG_ARC_PIE);
    imagefilledarc($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 360, $color, IMG_ARC_PIE);
    imagefilledarc($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 0, 360, $color, IMG_ARC_PIE);
    // middle fill, left fill, right fill
    imagerectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagerectangle($img, $x1, $y1 + $r, $x1 + $r, $y2 - $r, $color);
    imagerectangle($img, $x2 - $r, $y1 + $r, $x2, $y2 - $r, $color);
}

function getBoundingBox($intAngle, $intWidth, $intHeight)
{
    $fltRadians = deg2rad($intAngle);
    $intWidthRotated = $intHeight * abs(sin($fltRadians)) + $intWidth * abs(cos($fltRadians));
    $intHeightRotated = $intHeight * abs(cos($fltRadians)) + $intWidth * abs(sin($fltRadians));

    return array($intWidthRotated, $intHeightRotated);
}

class GVASParser
{

    var $position = 0;
    var $x = '';
    var $goldenBucket = array();
    var $last = false;

    /**
     * @param $x
     * @return false|string
     */
    public function parseData($x)
    {
        $this->x = $x;
        $this->position = 0;

        $this->goldenBucket = array();

        $a = array(
            'SHeader' => 32,
            'cSaveGameVersion' => 32,
            'PackageVersion' => 32,
            'EngineVersion.Major' => 16,
            'EngineVersion.Minor' => 16,
            'EngineVersion.Patch' => 16,
            'EngineVersion.Build' => 16,
            'EngineVersion.BuildId' => 32,
            'CustomFormatVersion' => 32,
            'CustomFormatData' => 32,
        );


        $this->position = 0;
        foreach ($a as $elem => $bits) {
//            echo $elem . " ";
            $value = mb_substr($x, $this->position, $bits / 8);
            if (mb_substr($elem, 0, 1) == 'S') {
                $value = array('1' => $value);
            } else {
                if ($bits == 16) {
                    $value = unpack('S', $value);
                }
                if ($bits == 32) {
                    $value = unpack('I', $value);
                }
            }
//            echo $value[1] . "\n";
            $this->position += $bits / 8;

            if ($elem == 'EngineVersion.BuildId') {
                $str = substr($x, $this->position, $value[1]);
                $this->position += $value[1];
//                echo "String is [$str]\n";
            }
        }
        $dataObjects = $value[1];
//        echo "Reading $dataObjects data objects...\n";

        for ($i = 0; $i < $dataObjects; $i++) {
            $id = substr($x, $this->position, 16);
            $this->position += 16;

            $val = unpack('I', substr($x, $this->position, 4))[1];
            $this->position += 4;
//            echo "($val)";
//            echo "index now at $this->position\n";
        }
        $this->position += 2;

//        echo "\n";
//        echo "File pointer is now at $this->position\n";
        $this->readUEString();
        while ($this->position < strlen($x)) {
            $this->readUEProperty();
        }

        $silverPlate = array();
        $keys = array_keys($this->goldenBucket);
        $keys = array('Player', 'Freight', 'Compressor', 'Tender', 'Coupler', 'Boiler', 'Headlight', 'Frame', 'Watertower', 'Switch');
        foreach ($keys as $key) {
            $silverPlate[$key . 's'] = array();
            foreach ($this->goldenBucket[$key] as $index => $value) {
                foreach ($value as $idx => $v) {
                    $silverPlate[$key . 's'][$idx][$index] = $v;
                }
            }
            unset($this->goldenBucket[$key]);
            $this->goldenBucket[$key . 's'] = $silverPlate[$key . 's'];
        }

        foreach ($this->goldenBucket['Frames'] as $i => $frame) {
            $this->goldenBucket['Frames'][$i]['Boiler'] = $this->goldenBucket['Boilers'][$i];
            $this->goldenBucket['Frames'][$i]['Headlights'] = $this->goldenBucket['Headlights'][$i];
            $this->goldenBucket['Frames'][$i]['Freight'] = $this->goldenBucket['Freights'][$i];
            $this->goldenBucket['Frames'][$i]['Compressor'] = $this->goldenBucket['Compressors'][$i];
            $this->goldenBucket['Frames'][$i]['Tender'] = $this->goldenBucket['Tenders'][$i];
            $this->goldenBucket['Frames'][$i]['Coupler'] = $this->goldenBucket['Couplers'][$i];
            $this->goldenBucket['Frames'][$i]['Regulator'] = $this->goldenBucket['Regulatorvalue'][$i];
            $this->goldenBucket['Frames'][$i]['Brake'] = $this->goldenBucket['Brakevalue'][$i];
            $this->goldenBucket['Frames'][$i]['Reverser'] = $this->goldenBucket['Reverservalue'][$i];
            $this->goldenBucket['Frames'][$i]['Smokestack'] = $this->goldenBucket['Smokestacktype'][$i];
            $this->goldenBucket['Frames'][$i]['Generatorvalvevalue'] = $this->goldenBucket['Generatorvalvevalue'][$i];
            $this->goldenBucket['Frames'][$i]['Marker']['Front']['Right'] = $this->goldenBucket['Marker']['Lights']['Front']['Right'][$i];
            $this->goldenBucket['Frames'][$i]['Marker']['Front']['Left'] = $this->goldenBucket['Marker']['Lights']['Front']['Left'][$i];
            $this->goldenBucket['Frames'][$i]['Marker']['Rear']['Right'] = $this->goldenBucket['Marker']['Lights']['Rear']['Right'][$i];
            $this->goldenBucket['Frames'][$i]['Marker']['Rear']['Left'] = $this->goldenBucket['Marker']['Lights']['Rear']['Left'][$i];
        }

        unset($this->goldenBucket['Headlights']);
        unset($this->goldenBucket['Boilers']);
        unset($this->goldenBucket['Freights']);
        unset($this->goldenBucket['Compressors']);
        unset($this->goldenBucket['Tenders']);
        unset($this->goldenBucket['Couplers']);
        unset($this->goldenBucket['Regulatorvalue']);
        unset($this->goldenBucket['Brakevalue']);
        unset($this->goldenBucket['Reverservalue']);
        unset($this->goldenBucket['Smokestacktype']);
        unset($this->goldenBucket['Generatorvalvevalue']);
        unset($this->goldenBucket['Marker']);

        for ($i = 0; $i < sizeof($this->goldenBucket['Industry']['Type']); $i++) {
            $this->goldenBucket['Industries'][] = array(
                'Type' => $this->goldenBucket['Industry']['Type'][$i],
                'Location' => $this->goldenBucket['Industry']['Location'][$i],
                'Rotation' => $this->goldenBucket['Industry']['Rotation'][$i],
                'EductsStored' => array(
                    $this->goldenBucket['Industry']['Storage']['Educt1'][$i],
                    $this->goldenBucket['Industry']['Storage']['Educt2'][$i],
                    $this->goldenBucket['Industry']['Storage']['Educt3'][$i],
                    $this->goldenBucket['Industry']['Storage']['Educt4'][$i],),
                'ProductsStored' => array(
                    $this->goldenBucket['Industry']['Storage']['Product1'][$i],
                    $this->goldenBucket['Industry']['Storage']['Product2'][$i],
                    $this->goldenBucket['Industry']['Storage']['Product3'][$i],
                    $this->goldenBucket['Industry']['Storage']['Product4'][$i],
                )
            );
        }


        for ($i = 0; $i < sizeof($this->goldenBucket['Spline']['Points']['Index']['Start']); $i++) {
            $locArr = $this->goldenBucket['Spline']['Location'][$i];

            $spline = array(
                'Location' => array(
                    'X' => $locArr[0],
                    'Y' => $locArr[1],
                    'Z' => $locArr[2]
                ),
                'Type' => $this->goldenBucket['Spline']['Type'][$i]
            );

            $segmentArray = array();
            if (!isset($this->goldenBucket['Spline']['Points']['Index']['Start'][$i])) die('xcxvxcv');
            $startPos = $this->goldenBucket['Spline']['Points']['Index']['Start'][$i];
            $endPos = $this->goldenBucket['Spline']['Points']['Index']['End'][$i];

            while ($startPos < $endPos) {

                $startLocs = $this->goldenBucket['Spline']['Points']['Arr'][$startPos];
                $endLocs = $this->goldenBucket['Spline']['Points']['Arr'][$startPos + 1];

                $segmentArray[] = array(
                    'LocationStart' => array(
                        'X' => $startLocs[0],
                        'Y' => $startLocs[1],
                        'Z' => $startLocs[2]
                    ),
                    'LocationEnd' => array(
                        'X' => $endLocs[0],
                        'Y' => $endLocs[1],
                        'Z' => $endLocs[2]
                    ),
                    'Visible' => array_shift($this->goldenBucket['Spline']['Segments']['Visibility']),

                );
                $startPos++;
            }

            $spline['Segments'] = $segmentArray;
            $this->goldenBucket['Splines'][] = $spline;
        }

        if (isset($this->goldenBucket['Turntable'])) {
            for ($i = 0; $i < sizeof($this->goldenBucket['Turntable']['Type']); $i++) {
                $this->goldenBucket['Turntables'][] = array(
                    'Type' => $this->goldenBucket['Turntable']['Type'][$i],
                    'Location' => $this->goldenBucket['Turntable']['Location'][$i],
                    'Rotator' => $this->goldenBucket['Turntable']['Rotator'][$i],
                    'Deck' => $this->goldenBucket['Turntable']['Deck']['Rotation'][$i]
                );
            }
        }
        unset($this->goldenBucket['Turntable']);
        unset($this->goldenBucket['Spline']);
        unset($this->goldenBucket['Save']);
        unset($this->goldenBucket['Industry']);


        $json = json_encode($this->goldenBucket, JSON_PRETTY_PRINT);
        file_put_contents('xx.json', $json);
        return $json;

    }

    function readUEString()
    {

//        echo "trying to read string at pos $this->position\n";
        if (substr($this->x, $this->position, 1) == NULL) {
//        echo "reading string started with 0\n";
            $this->position++;
            return null;
        }
        $value = substr($this->x, $this->position, 4);
        $this->position += 4;
        $value = unpack('i', $value)[1];
//    echo "trying to read $value Bytes of string\n";
        if ($value == 0) {
//        echo "j";
            return null;
        }
        if ($value == 1) {
//        echo "l";
            return "";
        }
        if ($value < 0) {
            //special encoding
            $value *= -2;
            $string = mb_convert_encoding(substr($this->x, $this->position, $value), "UTF-8", "UTF-16LE");
            //echo "[$string]";
            $this->position += $value;
        } else {
            $string = substr($this->x, $this->position, $value);
            $this->position += $value;
        }

        return trim($string); //Utf8.GetString(valueBytes, 0, valueBytes.Length - 1);
    }

    function readUEProperty()
    {

//    echo "Filepointer is now at $index\n";

        if (substr($this->x, $this->position, 1) < 0) {
//        echo "y";
            return null;
        }

        $name = $this->readUEString();
        if ($name == null) {
//        echo "NAME IS NULL\n";
            return null;
        }

        if ($name == "None")
            return new stdClass();

        $type = $this->readUEString();
        $value = substr($this->x, $this->position, 8);
        $this->position += 8;
        if ($this->position > strlen($this->x)) return; // EOF
        $value = unpack('q', $value)[1];

        $this->dUEDeserialize($name, trim($type), $value);
    }

    function magic($pieces, $something)
    {

        $current = implode('', $pieces);
        if ($current != $this->last) {
            //echo "(" . implode('', $pieces) . ")\n";
            $this->last = $current;
        }
//    if($current=='SplineStart'){
//        $goldenBucket['Spline']['Start'][] = $goldenBucket['Spline']['Points'][$something];
//    } elseif ($current=='SplineEnd') {
//        $goldenBucket['Spline']['End'][] = $goldenBucket['Spline']['Points'][$something];
//    } else {
        $code = '$this->goldenBucket[\'' . implode("']['", $pieces) . '\'][]=$something;';
        eval($code);
//    }
    }


    function dUEDeserialize($name, $type, $propertyLength)
    {

        $name = trim(str_replace(
            array('PointsArray', 'Array', 'GeneratorValveValue', 'ReverserValue', 'BrakeValue', 'RegulatorValue', 'SmokestackType', 'SwitchState',
                'XP', 'Control', 'FuelAmount', 'WaterTemp', 'WaterLevel', 'FireTemp',
                'AirPressure', 'WaterAmount', 'State', 'ValveValue', 'Assets'),

            array('PointsArr', '', 'Generatorvalvevalue', 'Reverservalue', 'Brakevalue', 'Regulatorvalue', 'Smokestacktype', 'SwitchSide', 'Xp',
                '', 'Fuelamount', 'Watertemp', 'Waterlevel', 'Firetemp', 'Airpressure', 'Wateramount', '', 'Valvevalue', '')
            , $name));

        $pieces = preg_split('/(?=[A-Z])/', $name);
        if (!$pieces[0]) array_shift($pieces);

        //echo "\n\nType: $type, Name: $name, Length: $propertyLength\n";

        switch ($type) {
            case 'StrProperty':
                $this->position++;
                $propertyLength = $this->readUEString();
                //$goldenBucket[$name][] = $propertyLength;
                $this->magic($pieces, $propertyLength);
                //echo "[$value]";
                break;
            case 'ArrayProperty':
                $itemType = trim($this->readUEString());
                //echo "ITEMTYPE:[$itemType]";
                $this->position++;
                $arrayCount = $val = unpack('I', substr($this->x, $this->position, 4))[1];
                $this->position += 4;
                //echo "ARRAYCOUNT:[$arrayCount]";
                switch ($itemType) {
                    case 'StrProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $str = trim($this->readUEString());
                            //@$goldenBucket[$name][]= $str;
                            $this->magic($pieces, $str);
                        }
                        break;
                    case 'StructProperty' :
                        $name = trim($this->readUEString());
                        $type = trim($this->readUEString());
                        $lenght = $val = unpack('P', substr($this->x, $this->position, 8))[1];
                        $this->position += 8;
//                    echo "[[$type][$lenght]]";
                        $subType = trim($this->readUEString());
                        $this->position++;
                        $this->position += 16;
                        switch ($subType) {
                            case "Vector":
                            case "Rotator":
                                for ($i = 0; $i < $arrayCount; $i++) {
                                    $notX = unpack('g', substr($this->x, $this->position, 4))[1];
                                    $this->position += 4;
                                    $y = unpack('g', substr($this->x, $this->position, 4))[1];
                                    $this->position += 4;
                                    $z = unpack('g', substr($this->x, $this->position, 4))[1];
                                    $this->position += 4;
                                    //$goldenBucket[$name][] = array($notX, $y, $z);
                                    $this->magic($pieces, array($notX, $y, $z));
                                }
                                break;
                            default:
                                echo "$name not implemented BOO\n";
                                die();
                        }
                        break;

                    case 'FloatProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $float = unpack('g', substr($this->x, $this->position, 4))[1];
                            $this->position += 4;
                            //@$goldenBucket[$name][]= $float;
                            $this->magic($pieces, $float);
                        }
                        break;

                    case 'IntProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $int = unpack('V', substr($this->x, $this->position, 4))[1];
                            $this->position += 4;
                            //@$goldenBucket[$name][]= $int;
                            $this->magic($pieces, $int);
                        }
                        break;

                    case 'BoolProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $bool = unpack('C', substr($this->x, $this->position, 1))[1];
                            $this->position += 1;
                            //@$goldenBucket[$name][]= $int;
                            $this->magic($pieces, $bool);
                        }
                        break;

                    case 'TextProperty':
                        //echo "Textproperty incoming at " . $this->position . " for Name $name\n";
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $cartText = $this->readTextProperty();
                            $this->magic($pieces, $cartText);
                            //echo "...-[$cartText]-..."
                        }
                        //$index+=$propertyLength-4;
//                    echo "... done\n";
                        break;

                    default:
                        //print_r($goldenBucket);

                        die($itemType . ' not implemented');
                }
                break;

            default:
                echo "unhandled type [$type]\n";
        }

//    echo "Type: ".substr($type,0,20).", Name: ".substr($name,0,20).", Lenght: ".substr($propertyLength,0,20);
    }


    function readTextProperty()
    {
        $terminator = unpack('C', substr($this->x, $this->position, 1))[1];
        $this->position++;
        $firstFour = unpack('i', substr($this->x, $this->position, 4))[1];
        $this->position += 4;
        $secondFour = unpack('i', substr($this->x, $this->position, 4))[1];
        $this->position += 4;

        switch ($terminator) {
            case 0 :
                $cartText = "";
                break;

            case 1:
                $s = $this->position - 9;
                $this->position += 5;
                $typeOfNextThing = $this->readUEString();
                $stringFormatter = $this->readUEString();
                $fourByteInt = unpack('i', substr($this->x, $this->position, 4))[1];
                $this->position += 4;
                for ($pp = 0; $pp < $fourByteInt; $pp++) {
                    $rowId = $this->readUEString();
                    $test = unpack('C', substr($this->x, $this->position, 1))[1];
                    $this->position++;
                    if ($test != 4) {
                        die('horribly');
                    } else {
                        $cartText[] = $this->readTextProperty();
                    }
                }
                foreach ($cartText as $placeholder => $elem) {
                    $stringFormatter = str_replace('{' . $placeholder . '}', $elem, $stringFormatter);
                }
                $cartText = $stringFormatter;

                $e = $this->position;
                //$cartText.='{'.($e-$s).'}';
                break;

            case 2:
                if ($secondFour == 0) {
                    $cartText = '';
                    break;
                }
                if ($secondFour == 1) {
                    $cartText = $this->readUEString();
                    break;
                }
                die('what is an exception?');
                break;

            default:
                die('oooops' . $terminator);
        }

        return $cartText;
    }
}
