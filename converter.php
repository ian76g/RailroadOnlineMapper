<?php
ini_set('memory_limit', -1);  // just in case - previous versions had 8000x8000 px truecolor images JPEG
set_time_limit(10);                // just in case something wents really bad -- kill script after 10 seconds
$v = 46;                                  //version - totally not used except in next line
echo "\n" . 'running converter version 0.' . $v . "\n";

/**
 * define some stuff we need later
 */
$start = microtime(true);           // to caclulate runtime at end
$imageWidth = 8000;                        // desired images width (x,y) aproximately

$path = 'uploads';                         // set the path to find the save games.... last line wins

if (isset($argv[1]) && !empty($argv[1])) {
    $path = pathinfo($argv[1])['dirname']; // run from commandline grabbing path from parameter
}

$empty = false;
if (isset($_POST['empty']) && $_POST['empty']) {
    $empty = true;                          // render table of rolling stock without names?
}

$bg = 'bg5';                                // choose a background image
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

// each different background image needs different croping and stretching params
$bgOffsets = array(
    'bg5' => array(9400, 9600, -720, -770, 9400, 9600),
    'bg4' => array(9400, 9600, -720, -770, 9400, 9600),
    'bg3' => array(8000, 8000, 0, 50, 8000, 8000),
    'bg' => array(8000, 8000, 0, 0, 8000, 8000),
);

// devine the SVG structure of the output-map
$htmlSvg = '<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Railroads Online Map</title>
    <script src="svg-pan-zoom.js"></script>
    <style>.myStuff {font-family: Verdana; font-size: 8pt;}</style>
  </head>
  <body>
    <div id="container" style="width: 850px; height: 850px; border:1px solid black; float:left">
      <svg id="demo-tiger" xmlns="http://www.w3.org/2000/svg" style="display: inline; width: inherit; min-width: inherit; max-width: inherit; height: inherit; min-height: inherit; max-height: inherit; " viewBox="0 0 8000 8000">
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

// later you can switch cargo on carts - maybe this can be done by editing the save via the mapper later?
$possibleCargos = array(
    'flatcar_logs' => array('log'),
    'flatcar_stakes' => array('rail', 'lumber', 'beam', 'rawiron'),
    'flatcar_hopper' => array('ironore', 'coal'),
    'flatcar_cordwood' => array('cordwood'),
);


/**
 * read all save files
 */
$dh = opendir($path);
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

if (!isset($NEWUPLOADEDFILE)) {                         // will be set in upload.php after upload.
    if (isset($argv[1])) {
        $NEWUPLOADEDFILE = $argv[1];                    // or we find it on command line
    } else {
        if (isset($_POST['save']) && $_POST['save']) {  // but this can also be aquired after editing the save out of the map.html
            $path = 'saves';
            $NEWUPLOADEDFILE = $_POST['save'];
        } else {
            die('no file submitted' . "\n");            // you either call it from commandline, upload or edit.
        }
    }
}

$files = array($NEWUPLOADEDFILE);                       // override allFiles with just the one file specified

$arithmeticHelper = new ArithmeticHelper();             // put some math stuff in an extra class
/**
 * /*
 * do all files that need to be done  (was overriden by a single file)
 */
foreach ($files as $file) {
    $htmlFileName = str_replace('.sav', '', basename($file)) . '.html';

    $doSvg = true;                                      // set this to false if you dont want a map
                                                        // previously a switch between SVG and JPEG output

    $svg = '';
    $savegame = $path . "/" . $file;

    $myParser = new GVASParser();                       // grab a Parser
    $myParser->NEWUPLOADEDFILE = $NEWUPLOADEDFILE;      // give the parser a filename

    $data = $myParser->parseData(file_get_contents($path . '/' . $file));
    if ($data == 'AGAIN') {
        // hack - we read a small struct - and inject new structure elements (empty cart numbers)
        // therefore we need to parse the new struct again.
        $data = $myParser->parseData(file_get_contents($path . '/' . $file), false);
    }
    $data = json_decode($data, true);

    /**
     * find min and max X and Y values in the save
     * whoever built track built it "somewhere"....
     *
     * initially to scale the network - but was skipped after getting the high quality backgrounds
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

    /**
     * Now we need a factor to scale the ingame coordinates of the network to our 8000px image
     */

    $max = max($x, $y);
    $scale = ($imageWidth * 100 / $max);

    $switchRadius = (80 / 2.2107077) * $scale;              // (size an armlength of switches)
    $engineRadius = 6 * $scale;                             // (radius of locomotives and carts)

    $turnTableRadius = (10 / 2.2107077) * $scale;           // size of turntables

    $imx = (int)$x / 100 * $scale;
    $imy = (int)$y / 100 * $scale;


    // OK - Now lets draw a map

    /**
     * set some basic order on what to draw first, rails of course should be painted last
     *
     * some info in the JSON is wrong - issue on github is created
     */

    $order = array(
        '1' => array(15, 'darkkhaki'),                      // variable bank
        '2' => array(15, 'darkkhaki'),                      //  constant bank
        '5' => array(15, 'darkgrey'),                       // variable wall
        '6' => array(15, 'darkgrey'),                       // constant wall
        '7' => array(15, 'lightblue'),                      //  iron bridge
        '3' => array(15, 'orange'),                         //  wooden bridge
        '4' => array( 3, 'black'),                          // trendle track
        '0' => array( 3, 'black'),                          // track  darkkhaki, darkgrey,orange,blue,black
    );

    // statistics for the webpage index
    $totalTrackLength = 0;
    $totalSwitches = 0;
    $totalLocos = 0;
    $totalCarts = 0;
    $maxSlope = 0;

    /**
     * Loop the order array painting one type over the next
     */
    foreach ($order as $current => $optionsArr) {
        foreach ($data['Splines'] as $spline) {
            $type = $spline['Type'];

            if ($type != $current) continue;            // if this spline is not the current type, skip it
            $segments = $spline['Segments'];
            foreach ($segments as $segment) {
                if ($segment['Visible'] != 1) continue; // skip invisible tracks

                if ($doSvg) {
                    $svg .= '<line x1="' .
                        ($imx - (int)(($segment['LocationStart']['X'] - $minX) / 100 * $scale)) . '" y1="' .
                        ($imy - (int)(($segment['LocationStart']['Y'] - $minY) / 100 * $scale))
                        . '" x2="' . ($imx - (int)(($segment['LocationEnd']['X'] - $minX) / 100 * $scale)) . '" y2="' .
                        ($imy - (int)(($segment['LocationEnd']['Y'] - $minY) / 100 * $scale))
                        . '" stroke="' . $optionsArr[1] . '" stroke-width="' . $optionsArr[0] . '"/>' . "\n";
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

                // label some splines with their slope - not yet working
                // main problem: find a spot for the text that is near to track but do not override other stuff
                if (false && $distance > 0 && in_array($type, array(4, 0))) {
                    $slope = asin(($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y']) / $distance) / pi() * 180;
                    if ($slope < -2 || $slope > 2) {
                    }
                }
            }
        }
    }

    /**
     * Fill in the missing gaps AKA switches
     */
    $types = array();
    foreach ($data['Switchs'] as $switch) {
        $dir = false;
        $type = trim($switch['Type']);

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
            case 3 :
            case 4:
                $dir = 7;
                break;
            case 2 :
                $dir = -7;
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
        $rotCross = deg2rad($switch['Rotation'][1] + 180);

        if ($doSvg) {
            $x = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale));
            $y = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale));
            if ($dir == 99) { //CROSS
                $crosslength = $switchRadius / 10;

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
                $xStraight = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotation) * $switchRadius / 2));
                $yStraight = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotation) * $switchRadius / 2));
                $xSide     = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotSide) * $switchRadius / 2));
                $ySide     = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotSide) * $switchRadius / 2));

                if ($state) {
//                    $svg .= '<text x="' . $x . '" y="' . $y . '">   ' . $type . '/' . $state . '</text>';
                    $svg .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . $xStraight . '" y2="' . $yStraight . '" stroke="red" stroke-width="3"/>' . "\n";
                    $svg .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . $xSide . '" y2="' . $ySide . '" stroke="black" stroke-width="3"/>' . "\n";
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
//    $colorTrack, $type);

    }


    /**
     * Fill in more missing gaps AKA turntables
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

        // fix given angles and convert to radiant - subtract 90 - because ingame coordinates do not point NORTH (!?)
        $rotation = deg2rad($table['Rotator'][1] + 90);
        $rotation2 = deg2rad($table['Rotator'][1] + 90 - $table['Deck'][1]);

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


    /**
     * draw some vehicles on top of that image
     * define size and color of vehicle here
     */

    $cartColors = array(
        'handcar' => array($engineRadius, 'black'),
        'porter_040' => array($engineRadius, 'black'),
        'porter_042' => array($engineRadius,  'black'),
        'eureka' => array($engineRadius,  'black'),
        'eureka_tender' => array($engineRadius,  'black'),
        'climax' => array($engineRadius,  'black'),
        'heisler' => array($engineRadius,  'black'),
        'class70' => array($engineRadius,  'black'),
        'class70_tender' => array($engineRadius,  'black'),
        'cooke260' => array($engineRadius,  'black'),
        'cooke260_tender' => array($engineRadius,  'black'),
        'flatcar_logs' => array($engineRadius / 3,  'red'),
        'flatcar_cordwood' => array($engineRadius / 3 * 2,  'orange'),
        'flatcar_stakes' => array($engineRadius / 3 * 2,  'yellow'),
        'flatcar_hopper' => array($engineRadius / 3 * 2,  'brown'),
        'flatcar_tanker' => array($engineRadius / 3 * 2,  'grey'),
    );

    // build some extra HTML for a form to edit cart data
    $cartExtraStr = '<form method="POST" action="../converter.php"><input type="hidden" name="save" value="' . $NEWUPLOADEDFILE . '">
<table class="myStuff">
<tr>
<th>Type</th>
<th>Name</th>
<th>Number</th>
<th>near</th>
<th>Cargo</th>
<th>Amount</th>
</tr>
###TROWS###</table><input type="submit" value="RENUMBER"></form>';
    $trows = '';
    foreach ($data['Frames'] as $cartIndex => $vehicle) {
        $trow = '<tr>
<td>###1###</td>
<td>              <input size="5" maxlength="15" name="name_' . $cartIndex . '" value="###2###"></td>
<td align="right"><input size="5" maxlength="15" name="number_' . $cartIndex . '" value="###3###"></td>
<td>###4###</td>
<td>###5###</td>
<td>###6###</td>
</tr>';
        $exArr = array($vehicle['Type'], strtoupper(strip_tags($vehicle['Name'])), strip_tags(trim($vehicle['Number'])));
        if (
            // trim out empty carts without number and without name
            $empty ||
            (strip_tags($vehicle['Name']) && trim($vehicle['Name']) != '.'  ) ||
            (trim($vehicle['Number']) != '.' && trim($vehicle['Number']))
        )
        {
            $exArr[] = $arithmeticHelper->nearestIndustry($vehicle['Location'], $data['Industries']);
            if ($vehicle['Tender']['Fuelamount']) {
                $exArr[] = 'firewood';
                $exArr[] = $vehicle['Tender']['Fuelamount'];
                $exArr[] = 'tenderamount_';
            } else {
                if ($vehicle['Freight']['Type']) {
                    $exArr[] = $vehicle['Freight']['Type'];
                    $exArr[] = $vehicle['Freight']['Amount'];
                    $exArr[] = 'freightamount_';
                } else {
                    $exArr[] = '-';
                    $exArr[] = '-';
                    $exArr[] = false;

                }

            }
            if ($exArr[6]) {
                $template = '<input size="2" maxlength="2" name="' . $exArr[6] . $cartIndex . '" value="' . $exArr[5] . '">';
            } else {
                $template = $exArr[5];
            }
            $exArr[5] = $template;

            $trows .= str_replace(array('###1###', '###2###', '###3###', '###4###', '###5###', '###6###'), $exArr, $trow);

        }

        $x = ($imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale));
        $y = ($imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale));
        if ($doSvg) {
            $svg .= '<ellipse cx="' . $x . '" cy="' . $y . '" rx="' . ($engineRadius / 2) . '" ry="' . ($engineRadius / 3) .
                '" style="fill:' . $cartColors[$vehicle['Type']][1] . ';stroke:black;stroke-width:1" transform="rotate(' . $vehicle['Rotation'][1] .
                ', ' . ($imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale)) . ', ' . ($imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale)) . ')"
              />';

            if ($vehicle['Location'][2] < 1000) {
                $svg .= '<ellipse cx="' . $x . '" cy="' . $y . '" rx="' . (($engineRadius / 2) * 10) .
                    '" ry="' . (($engineRadius / 2) * 10) .
                    '" style="fill:none;stroke:red;stroke-width:10" transform="rotate(' . $vehicle['Rotation'][1] .
                    ', ' . ($imx - (int)(($vehicle['Location'][0] - $minX) / 100 * $scale)) . ', ' . ($imy - (int)(($vehicle['Location'][1] - $minY) / 100 * $scale)) . ')"
              />';
                $svg .= '<text x="' . $x . '" y="' . $y . '" >' . '&nbsp;&nbsp;' . $vehicle['Location'][2] . '</text>' . "\n";

            }
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
            if ($doSvg) {
                $svg .= '<text x="' . $x . '" y="' . $y . '" >' . '&nbsp;&nbsp;' . $name . '</text>' . "\n";

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
                $name .= "(" . array_sum($site['ProductsStored']) . ")";
                $rotation = 0;
                break;
            default:
                die('unknown industry');
        }


        // create a "database" and store some infos about this file for the websies index page
        @$db = unserialize(@file_get_contents('db.db'));
        $db[$NEWUPLOADEDFILE] = array($totalTrackLength, $totalSwitches, $totalLocos, $totalCarts, $maxSlope);
        @file_put_contents('db.db', serialize($db));

        // label the industries
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
     * chart (Legende) of carts
     */
    $carts = array(
        'flatcar_logs' => array($engineRadius / 3, 'red'),
        'flatcar_cordwood' => array($engineRadius / 3 * 2, 'orange'),
        'flatcar_stakes' => array($engineRadius / 3 * 2, 'yellow'),
        'flatcar_hopper' => array($engineRadius / 3 * 2, 'brown'),
        'flatcar_tanker' => array($engineRadius / 3 * 2, 'grey'),
    );

    $cartsDrawn = 0;
    foreach ($carts as $cartType => $cart) {
        $x = $imageWidth / 4;
        $y = 100 + $cartsDrawn * 5 * ($engineRadius * 1.1) / 2;
        $rx = $engineRadius * 1.1 * 2;
        $ry = ($engineRadius * 1.1);
        if ($doSvg) {
            $svg .= '<ellipse cx="' . $x .
                '" cy="' . $y .
                '" rx="' . $rx .
                '" ry="' . $ry .
                '" style="fill:' . $cart[1] . ';stroke:black;stroke-width:1" />' . "\n";
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


    //@print_r($distances);
    if ($doSvg) {
        file_put_contents('done/' . $htmlFileName, str_replace('&nbsp;', ' ', str_replace('###SVG###', $svg, $htmlSvg)));

        // optionally pushing this automatically to a webserver
        // if you run this script as deamon - it uploads on each save automatically
        $cmd = '"c:\Program Files (x86)\WinSCP\WinSCP.com" /command "open ftp://user:password@server.de/" "put ' . $htmlFileName . ' /html/minizwerg/" "exit"';
//        passthru($cmd);
    }

    echo "rendered in " . (microtime(true) - $start) . " microseconds\n";

    // store save for 5 minutes in case someone wants to edit the cart texts/numbers
    if (!isset($_POST['save'])) {
        rename('uploads/' . $NEWUPLOADEDFILE, 'saves/' . $NEWUPLOADEDFILE);
    }
}
//debug
// print_r($types);


/**
 * Class dtHeader for the Fileheader
 */
class dtHeader
{
    var $NAME = 'HEADER';
    var $a = array(
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

    var $content = '';

    /**
     * @param $fromX
     * @param $position
     * @return float|int|mixed
     */
    function unserialize($fromX, $position)
    {
        foreach ($this->a as $elem => $bits) {

            if ($elem == 'EngineVersion.BuildId') {
                $substring = mb_substr($fromX, $position, 4);
                $substringUnpacked = unpack('I', $substring)[1];

                $position += 4;
                $this->content .= $substring;
                $str = substr($fromX, $position, $substringUnpacked);
                $this->content .= $str;
                $position += $substringUnpacked;
                continue;
            }

            $substring = mb_substr($fromX, $position, $bits / 8);
            $position += $bits / 8;
            if (substr($elem, 0, 1) == 'S') {
                //$substringUnpacked = array('1' => $substring);  // GVAS
                $this->content = 'GVAS';
            } else {
                if ($bits == 16) {
                    $substringUnpacked = unpack('S', $substring);
                    $this->content .= $substring;
                }
                if ($bits == 32) {
                    $substringUnpacked = unpack('I', $substring);
                    $this->content .= $substring;
                }
            }
        }


        $dataObjects = $substringUnpacked[1];
        for ($i = 0; $i < $dataObjects; $i++) {
            $id = unpack('h*', substr($fromX, $position, 16))[1];
            $this->content .= substr($fromX, $position, 16);
            $position += 16;

            $val = unpack('I', substr($fromX, $position, 4))[1];
            $this->content .= pack('I', $val);
            $position += 4;
        }

        //$this->content .= substr($fromX, $position, 2); // ???
        $position += 2;

        return $position;
    }

    function serialize()
    {
        return $this->content;
    }

}


/**
 * Class dtString
 */
class dtString
{
    var $content;
    var $string;
    var $x;
    var $position;
    var $nullBytes;
    var $NAME = 'STRING';
    var $skipNullByteStrings = true;
    var $ARRCOUNTER = '';

    /**
     * @param $fromX
     * @param $position
     * @return array
     */
    function unserialize($fromX, $position)
    {
        $this->x = $fromX;
        $this->position = $position;
        $this->readUEString();
        return array($this->string, $this->position);
    }

    /**
     *
     */
    function readUEString()
    {

        if (substr($this->x, $this->position, 1) == NULL) {
            $this->position++;
            $this->content = NULL;
            $this->string = NULL;
            $this->nullBytes = 0;
            return;
        }
        $value = substr($this->x, $this->position, 4);
        $this->position += 4;
        $this->content = $value;
        $value = unpack('i', $value)[1];

        if ($value == 0) {
            $this->string = null;
            $this->nullBytes = 0;
            return;
        }
        if ($value == 1) {
            $this->string = '';
            $this->nullBytes = 0;
            return;
        }
        if ($value < 0) {
            //special encoding
            $value *= -2;
            $string = mb_convert_encoding(substr($this->x, $this->position, $value), "UTF-8", "UTF-16LE");
            $this->string = $string;
            $this->content .= substr($this->x, $this->position, $value);
            $this->nullBytes =
                strlen(substr($this->x, $this->position, $value)) -
                strlen(rtrim(substr($this->x, $this->position, $value), "\0"));
            $this->position += $value;
        } else {
            $this->string = substr($this->x, $this->position, $value);
            $this->position += $value;
            $this->content .= $this->string;
            $this->nullBytes = strlen($this->string) - strlen(rtrim($this->string));
        }

        return;
    }

    /**
     * @return string
     */
    public function serialize()
    {

        if ($this->skipNullByteStrings && $this->string === NULL) {
            return NULL;
        }

        if (!$this->skipNullByteStrings && $this->string === NULL) {
            return pack('i', 0);
        }

        if (mb_detect_encoding($this->string) == 'UTF-8') {
            $data = mb_convert_encoding($this->string, "UTF-16LE", "UTF-8");
            $strLength = -(strlen($data) / 2);
            $data = pack('i', $strLength) . rtrim($data, "\0");
        } else {
            $data = pack('i', strlen($this->string)) . rtrim($this->string);
        }

        for ($i = 0; $i < $this->nullBytes; $i++) {
            $data .= hex2bin('00');
        }

        return $data;
    }

}

class dtDynamic
{
    var $value;
    var $NAME = 'dynamic';
    var $ARRCOUNTER = false;
    var $pack;

    function serialize()
    {
        return pack($this->pack, $this->value);
    }
}

class dtProperty
{
    var $x;
    var $position;
    var $last = false;
    var $CONTENTOBJECTS;
    var $NAME;
    var $TYPE;
    var $RESULTROWS;
    var $ITEMTYPE='';
    var $SUBTYPE='';
    var $GUID='';

    var $content;

    function unserialize($fromX, $position)
    {
        $this->x = $fromX;
        $this->position = $position;

        $resultRows = $this->readUEProperty();
        $this->RESULTROWS = $resultRows;
        return array($resultRows, $this->position);
    }

    /**
     * @return string
     */
    function serialize()
    {
        $output = '';
        foreach ($this->CONTENTOBJECTS as $element) {
            if (is_object($element)) {
                if (is_callable(array($element, 'serialize'))) {
                    $output .= $element->serialize();
                } else {
                    echo "found object " . get_class($element) . "...";
                    echo "FAILED TO SERIALIZE\n";
                    die();
                }
            } else {
                $output .= $element;
            }
        }

        return $output;
    }

    /**
     * @return array|array[]|stdClass|string
     */
    function readUEProperty()
    {

        //echo "Filepointer is now at $this->position\n";

        if (substr($this->x, $this->position, 1) < 0) {
            echo "YYYYyYYYY";
            $this->content = substr($this->x, $this->position, 1);
            return array(null, null, $this->position + 1);
        }

        $myString = new dtString();
        $results = $myString->unserialize($this->x, $this->position);
        $name = $results[0];
//    echo "$name ";
        $this->position = $results[1];
        $this->NAME = $name;
        $this->CONTENTOBJECTS[] = $myString;
//    echo "[$name]";
        if ($name == "None") {
            echo "XXXxXXX";
            return new stdClass();
        }

        $myString = new dtString();
        $results = $myString->unserialize($this->x, $this->position);
        $type = $results[0];
//   echo "$type ";
        $this->position = $results[1];
        $this->TYPE = trim($type);
        $this->CONTENTOBJECTS[] = $myString;
        $value = substr($this->x, $this->position, 8);
        $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 8);
        $this->position += 8;
        $this->GUID = $value;

        if ($this->position > strlen($this->x)) {
            return 'EOF'; // EOF
        }
        $stuff = $this->dUEDeserialize($name, $type);

        return $stuff;
    }

    /**
     * @param $name
     * @param $type
     * @return array|array[]
     */
    function dUEDeserialize($name, $type)
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

        switch (trim($type)) {
            case 'StrProperty':
                $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 1);
                $this->position++;  // WHY??
                $myString = new dtString();
                $results = $myString->unserialize($this->x, $this->position);
                $string = $results[0];
                $this->position = $results[1];
                //$goldenBucket[$name][] = $propertyLength;

                $this->CONTENTOBJECTS[] = $myString;

                return array(array($pieces, $string));

            case 'ArrayProperty':
                $elem = array();
                $myString = new dtString();
                $results = $myString->unserialize($this->x, $this->position);
                $itemType = trim($results[0]);
                $this->ITEMTYPE = $itemType;
                $this->position = $results[1];
                $this->CONTENTOBJECTS[] = $myString;

                $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 1);
                $this->position++;

                $arrayCount = unpack('I', substr($this->x, $this->position, 4))[1];
                $this->CONTENTOBJECTS[] = pack('I', $arrayCount);
                $this->position += 4;

//                echo "ARRAYCOUNT:[$arrayCount] ";
                switch ($itemType) {
                    case 'StrProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $myString = new dtString();
                            $myString->skipNullByteStrings = false;
                            $results = $myString->unserialize($this->x, $this->position);
                            $str = trim($results[0]);
                            $this->position = $results[1];
                            $myString->ARRCOUNTER = $i;
                            $this->CONTENTOBJECTS[] = $myString;
                            //@$goldenBucket[$name][]= $str;
                            $elem[] = array($pieces, $str);
                        }
                        return $elem;

                    case 'StructProperty' :
                        $myString = new dtString();
                        $results = $myString->unserialize($this->x, $this->position);
                        $name = trim($results[0]);
                        $this->position = $results[1];
                        $this->CONTENTOBJECTS[] = $myString;

                        $myString = new dtString();
                        $results = $myString->unserialize($this->x, $this->position);
                        $type = trim($results[0]);
                        $this->position = $results[1];
                        $this->CONTENTOBJECTS[] = $myString;

                        $lenght = $val = unpack('P', substr($this->x, $this->position, 8))[1];
                        $this->position += 8;
                        $this->CONTENTOBJECTS[] = pack('P', $lenght);

//                    echo "[[$type][$lenght]]";
                        $myString = new dtString();
                        $results = $myString->unserialize($this->x, $this->position);
                        $subType = trim($results[0]);
                        $this->SUBTYPE = $subType;
                        $this->position = $results[1];
                        $this->CONTENTOBJECTS[] = $myString;

                        $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 17);
                        $this->position++;
                        $this->position += 16;

                        switch ($subType) {
                            case "Vector":
                            case "Rotator":
                                for ($i = 0; $i < $arrayCount; $i++) {
                                    $notX = unpack('g', substr($this->x, $this->position, 4))[1];
                                    $this->CONTENTOBJECTS[] = pack('g', $notX);
                                    $this->position += 4;
                                    $y = unpack('g', substr($this->x, $this->position, 4))[1];
                                    $this->CONTENTOBJECTS[] = pack('g', $y);
                                    $this->position += 4;
                                    $z = unpack('g', substr($this->x, $this->position, 4))[1];
                                    $this->CONTENTOBJECTS[] = pack('g', $z);
                                    $this->position += 4;
                                    //$goldenBucket[$name][] = array($notX, $y, $z);
                                    $elem[] = array($pieces, array($notX, $y, $z));
                                }
                                return $elem;

                            default:
                                echo "$name not implemented BOO\n";
                                die();
                        }

                    case 'FloatProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $float = unpack('g', substr($this->x, $this->position, 4))[1];
                            $this->CONTENTOBJECTS[] = pack('g', $float);
                            $this->position += 4;
                            //@$goldenBucket[$name][]= $float;
                            $elem[] = array($pieces, $float);
                        }
                        return $elem;

                    case 'IntProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $int = unpack('V', substr($this->x, $this->position, 4))[1];
                            $nD = new dtDynamic();
                            $nD->NAME = 'Int';
                            $nD->value = $int;
                            $nD->ARRCOUNTER = $i;
                            $nD->pack = 'V';
                            $this->CONTENTOBJECTS[] = $nD;
                            $this->position += 4;
                            //@$goldenBucket[$name][]= $int;
                            $elem[] = array($pieces, $int);
                        }
                        return $elem;

                    case 'BoolProperty':
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $bool = unpack('C', substr($this->x, $this->position, 1))[1];
                            $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 1);
                            $this->position += 1;
                            //@$goldenBucket[$name][]= $int;
                            $elem[] = array($pieces, $bool);
                        }
                        return $elem;

                    case 'TextProperty':
                        //echo "Textproperty incoming at " . $this->position . " for Name $name\n";
                        for ($i = 0; $i < $arrayCount; $i++) {
                            $createEmptyNumber = false;
                            if (trim($this->NAME) == 'FrameNumberArray') {
                                $createEmptyNumber = true;
                            }
//                            if (trim($this->NAME) == 'FrameNameArray') {
//                                $createEmptyNumber = true;
//                            }
                            $cartText = $this->readTextProperty($i, $createEmptyNumber);
                            $elem[] = array($pieces, $cartText);
                            //echo "...-[$cartText]-..."
                        }
                        //$index+=$propertyLength-4;
//                    echo "... done\n";
                        return $elem;

                    default:
                        //print_r($goldenBucket);

                        die($itemType . ' not implemented');
                }

            default:
                echo "unhandled type [$type]\n";
                die();
        }

//    echo "Type: ".substr($type,0,20).", Name: ".substr($name,0,20).", Lenght: ".substr($propertyLength,0,20);
    }

    /**
     * @param $i
     * @param false $createEmptyNumer
     * @return array
     *
     * Jenny — heute um 08:16 Uhr
    There is another format (the strange one you sent me)
    Basically when you finish reading an entry and start reading the next one, you need to read the first int32 to know whether it’s formatted or not
    Usually you get 02 00 00 00 if there’s a regular text entry, 00 00 00 00 if it’s a null text entry, and 01 00 00 00 if it’s formatted
    If you get 02 or 00, then read the separator ff and the « opt » which is 01 00 00 00 if there’s a UEString, and 00 00 00 00 if there’s not.
    And then onto the next index of the array
    However if you get 01 00 00 00 as first value, then it’s formatted, the separator is 03, then int64 08 00 00 00 00 00 00 00 and empty byte 00
    Then the format specifiers : UEString (the magic string I don’t know what it does but is always the same), UEString (formatted) int 32 with value 02 00 00 00 (probably the number of field in the formatter) and one last UEString with "0"
    Then a special separator 04
    And then the first line as a special text property:
    02 00 00 00
    ff
    01 00 00 00
    Then 2 UEString
    The first one being the actual content of the first line, the second one being the "1" we always see, but that can be discarded when reading and put back when writing
    And that field ends with one byte 04
    And then the second line, which will always start with 02 00 00 00
    Then ff
    Then if it’s empty 00 00 00 00, or else 01 00 00 00 then UEString
    I don’t think it ends with 04 for that one (writing that from memory)
    And that’s the full formatted TextProperty array index
     */
    function readTextProperty($i, $createEmptyNumer = false)
    {
        $terminator = unpack('C', substr($this->x, $this->position, 1))[1];
        $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 1);
        $this->position++;
        $firstFour = unpack('i', substr($this->x, $this->position, 4))[1];
        $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 4); //000000FF
        $this->position += 4;
        $secondFour = unpack('i', substr($this->x, $this->position, 4))[1];
        $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 4); //00000000 in case of terminator 0
        $this->position += 4;

        if ($terminator == 0 && $createEmptyNumer) {
            // HERE WE HAVE TO IMPLEMENT A NEW CART NUMBER (eg. a dot)
            // TERMINATOR must be 02
            // firstFour have to be 000000FF
            // secondFour have to be 01000000
            // and then comes the text as length 2 bytes 02000000
            // and the text itself .0x00
            //
            $this->CONTENTOBJECTS[sizeof($this->CONTENTOBJECTS) - 3] = pack('C', 2);
            $this->CONTENTOBJECTS[sizeof($this->CONTENTOBJECTS) - 1] = pack('i', 1);
            $newText = new dtString();
            $newText->nullBytes = 1;
            $newText->string = '.' . hex2bin('00');
            $this->CONTENTOBJECTS[] = $newText;
        }

        switch ($terminator) {
            case 0 :
                $cartText = "";
                break;

            case 1:
                $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 5);  // UNKONWN 0000000308
                $this->position += 5;
                $myString = new dtString();
                $results = $myString->unserialize($this->x, $this->position);
                $typeOfNextThing = $results[0];
                $this->position = $results[1];
                $this->CONTENTOBJECTS[] = $myString;

                $myString = new dtString();
                $results = $myString->unserialize($this->x, $this->position);
                $stringFormatter = $results[0];
                $this->position = $results[1];
                $this->CONTENTOBJECTS[] = $myString;

                $fourByteInt = unpack('i', substr($this->x, $this->position, 4))[1];
                $this->position += 4;
                $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 4);

                $cartText = array();
                for ($pp = 0; $pp < $fourByteInt; $pp++) {
                    $myString = new dtString();
                    $results = $myString->unserialize($this->x, $this->position);
                    //$rowId = $results[0];
                    $this->position = $results[1];
                    $myString->ARRCOUNTER = $i;
                    $this->CONTENTOBJECTS[] = $myString;

                    $test = unpack('C', substr($this->x, $this->position, 1))[1];
                    $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 1);
                    $this->position++;
                    if ($test != 4) {
                        die('horribly');
                    } else {
                        $cartText[] = $this->readTextProperty($pp)[0];
                    }
                }
                foreach ($cartText as $placeholder => $elem) {
                    $stringFormatter = str_replace('{' . $placeholder . '}', $elem, $stringFormatter);
                }
                $cartText = $stringFormatter;

                break;

            case 2:
                if ($secondFour == 0) {
                    $cartText = '';
                    break;
                }
                if ($secondFour == 1) {
                    $myString = new dtString();
                    $results = $myString->unserialize($this->x, $this->position);
                    $cartText = $results[0];
                    $this->position = $results[1];
                    $myString->ARRCOUNTER = $i;
                    $this->CONTENTOBJECTS[] = $myString;

                    break;
                }
                die('what is an exception?');

            default:
                die('oooops' . $terminator);
        }

        return array($cartText, $this->position);
    }
}

/**
 * Class GVASParser
 */
class GVASParser
{

    var $position = 0;
    var $x = '';
    var $goldenBucket = array();
    var $NEWUPLOADEDFILE;
    var $saveObject = array();

    /**
     * @param $x
     * @param bool $againAllowed
     * @return false|string
     */
    public function parseData($x, $againAllowed = true)
    {
        $this->x = $x;
        $this->position = 0;
        $this->goldenBucket = array();
        $this->position = 0;

        $myHeader = new dtHeader();
        $this->position = $myHeader->unserialize($this->x, $this->position);
        $headerTotal = substr($this->x, 0, $this->position);
        $myHeader->content = $headerTotal;

        $this->saveObject['objects'][] = $myHeader;

        $myString = new dtString();
        $results = $myString->unserialize($x, $this->position);
        $string = $results[0];
        $this->position = $results[1];
        $this->saveObject['objects'][] = $myString;

        while ($this->position < strlen($x)) {
            $myProperty = new dtProperty();
            $results = $myProperty->unserialize($this->x, $this->position);
            if ($results['0'] != 'EOF') {

                $original = substr($this->x, $this->position, $results[1] - $this->position);
                $test = $myProperty->serialize();
                if ($original != $test) {
                    file_put_contents('tmp_' . trim($myProperty->NAME), $original);
                    file_put_contents('tmp_' . trim($myProperty->NAME) . '.test', $test);

                }

                $this->saveObject['objects'][] = $myProperty;

                $this->position = $results[1];
                $resultRows = $results[0];
                foreach ($resultRows as $row) {
                    $pieces = $row[0];
                    $something = $row[1];
                    if (is_array($something) && sizeof($something) == 2) {
                        $something = $something[0];
                    }
                    $code = '$this->goldenBucket[\'' . implode("']['", $pieces) . '\'][]=$something;';
                    eval($code);
                }
            } else {
                break;
            }
        }

        $output = '';
        foreach ($this->saveObject['objects'] as $object) {
            if (is_object($object)) {
//                echo 'ON: '.trim($object->NAME).", ";
                if (trim($object->NAME) == 'FrameNumberArray') {
                    foreach ($object->CONTENTOBJECTS as $co) {
                        if (is_object($co) && trim($co->NAME) == 'STRING') {
                            if (
                                ($co->ARRCOUNTER !== '') &&
                                isset($_POST['number_' . $co->ARRCOUNTER]) &&
                                $_POST['number_' . $co->ARRCOUNTER] != trim($co->string)
                            ) {
                                $co->string = strip_tags(trim($_POST['number_' . $co->ARRCOUNTER])) . hex2bin('00');
                            }
                        }
                    }
                }
//                if (trim($object->NAME) == 'FrameNameArray') {
//                    foreach ($object->CONTENTOBJECTS as $co) {
//                        if (is_object($co) && trim($co->NAME) == 'STRING') {
//                            if (
//                                ($co->ARRCOUNTER !== '') &&
//                                isset($_POST['name_' . $co->ARRCOUNTER]) &&
//                                $_POST['name_' . $co->ARRCOUNTER] != trim($co->string)
//                            ) {
//                                $co->string = strip_tags(trim($_POST['name_' . $co->ARRCOUNTER])) . hex2bin('00');
//                            }
//                        }
//                    }
//                }
                if (trim($object->NAME) == 'FreightAmountArray') {
                    foreach ($object->CONTENTOBJECTS as $co) {
                        if (is_object($co) && trim($co->NAME) == 'Int') {
                            if (
                                ($co->ARRCOUNTER !== '') &&
                                isset($_POST['freightamount_' . $co->ARRCOUNTER]) &&
                                $_POST['freightamount_' . $co->ARRCOUNTER] != trim($co->value)
                            ) {
                                $co->value = strip_tags(trim($_POST['freightamount_' . $co->ARRCOUNTER]));
                            }
                        }
                    }
                }
                if (trim($object->NAME) == 'TenderFuelAmountArray') {
                    foreach ($object->CONTENTOBJECTS as $co) {
                        if (is_object($co) && trim($co->NAME) == 'Int') {
                            if (
                                ($co->ARRCOUNTER !== '') &&
                                isset($_POST['tenderamount_' . $co->ARRCOUNTER]) &&
                                $_POST['tenderamount_' . $co->ARRCOUNTER] != trim($co->value)
                            ) {
                                $co->value = strip_tags(trim($_POST['tenderamount_' . $co->ARRCOUNTER]));
                            }
                        }
                    }
                }

                $output .= $object->serialize();
            } else {
                die('WHOPSI');
            }
        }

        if (isset($_POST['save'])) {
            echo "SAVING FILE " . $this->NEWUPLOADEDFILE . '.modified' . "<br>\n";
            file_put_contents('saves/' . $this->NEWUPLOADEDFILE . '.modified', $output);
            echo '<A href="saves/' . $this->NEWUPLOADEDFILE . '.modified' . '">Download your modified save here </A><br>';
            echo 'Want to upload this map again?<A href="upload.php">Add your renumbered save again</A><br>';
        } else {
            if ($againAllowed) {
                echo "RESAVING FILE TO DISK - EMPTY NUMBERS BECAME A DOT " . $this->NEWUPLOADEDFILE . "<br>\n";
                file_put_contents('uploads/' . $this->NEWUPLOADEDFILE, $output);
                return 'AGAIN';
            }
        }

        $silverPlate = array();

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
}

class ArithmeticHelper
{

    function nearestIndustry($coords, $industryCoords)
    {
        $minDist = 800000;
        foreach ($industryCoords as $i) {
            if ($i['Type'] < 10) {
                $d = $this->dist($i['Location'], $coords);
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

}
