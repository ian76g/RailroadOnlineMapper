<?php
include_once 'GVAS.php';
ini_set('memory_limit', -1);  // just in case - previous versions had 8000x8000 px truecolor images JPEG
set_time_limit(10);                // just in case something wents really bad -- kill script after 10 seconds
$v = 46;                                  //version - totally not used except in next line
//echo "\n" . 'running converter version 0.' . $v . "\n";

function getUserIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

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
$htmlSvg = file_get_contents('map.template.html');
$pattern = '
<pattern id="bild" x="0" y="0" width="' . $bgOffsets[$bg][0] . '" height="' . $bgOffsets[$bg][1] . '" patternUnits="userSpaceOnUse">
                <image x="' .
    ((isset($_POST['xoff']) && $_POST['xoff'] !== '') ? ($_POST['xoff']) : $bgOffsets[$bg][2]) . '" y="' .
    ((isset($_POST['yoff']) && $_POST['xoff'] !== '') ? ($_POST['yoff']) : $bgOffsets[$bg][3]) . '" width="' .
    ((isset($_POST['xsoff']) && $_POST['xoff'] !== '') ? ($_POST['xsoff']) : $bgOffsets[$bg][4]) . '" height="' .
    ((isset($_POST['ysoff']) && $_POST['xoff'] !== '') ? ($_POST['ysoff']) : $bgOffsets[$bg][5]) . '" href="' . $bg . '.png" />
            </pattern>
';
$htmlSvg = str_replace('###PATTERN###', $pattern, $htmlSvg);


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
    if (!file_exists($path . '/' . $file)) {
        header('Location: https://minizwerg.online/');
        die();
    }
    $htmlFileName = str_replace('.sav', '', basename($file)) . '.html';
    $downloadLink = '';
    if (file_exists('public/' . basename($file))) {
        $downloadLink = '<A href="../public/' . basename($file) . '">Download Save</A>';
    }
    $htmlSvg = str_replace('###DOWNLOAD###', $downloadLink, $htmlSvg);


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
        '4' => array(3, 'black'),                          // trendle track
        '0' => array(3, 'black'),                          // track  darkkhaki, darkgrey,orange,blue,black
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
                $xSide = ($imx - (int)(($switch['Location'][0] - $minX) / 100 * $scale) + (cos($rotSide) * $switchRadius / 2));
                $ySide = ($imy - (int)(($switch['Location'][1] - $minY) / 100 * $scale) + (sin($rotSide) * $switchRadius / 2));

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
        'porter_042' => array($engineRadius, 'black'),
        'eureka' => array($engineRadius, 'black'),
        'eureka_tender' => array($engineRadius, 'black'),
        'climax' => array($engineRadius, 'black'),
        'heisler' => array($engineRadius, 'black'),
        'class70' => array($engineRadius, 'black'),
        'class70_tender' => array($engineRadius, 'black'),
        'cooke260' => array($engineRadius, 'black'),
        'cooke260_tender' => array($engineRadius, 'black'),
        'flatcar_logs' => array($engineRadius / 3, 'red'),
        'flatcar_cordwood' => array($engineRadius / 3 * 2, 'orange'),
        'flatcar_stakes' => array($engineRadius / 3 * 2, 'yellow'),
        'flatcar_hopper' => array($engineRadius / 3 * 2, 'brown'),
        'flatcar_tanker' => array($engineRadius / 3 * 2, 'grey'),
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
###TROWS###</table><input type="submit" value="!APPLY ABOVE CHANGES TO MY SAVE!"></form>';
    $trows = '';

    // later you can switch cargo on carts - maybe this can be done by editing the save via the mapper later?
    $possibleCargos = array(
        'flatcar_logs' => array('log'),
        'flatcar_stakes' => array('rail', 'lumber', 'beam', 'rawiron'),
        'flatcar_hopper' => array('ironore', 'coal'),
        'flatcar_cordwood' => array('cordwood'),
    );


    foreach ($data['Frames'] as $cartIndex => $vehicle) {
        $trow = '<tr>
<td>###1###</td>
<td>              <input size="5" maxlength="15" name="name_' . $cartIndex . '" value="###2###"></td>
<td align="right"><input size="5" maxlength="15" name="number_' . $cartIndex . '" value="###3###"></td>
<td>###4###</td>
<td>###5###</td>
<td>###6###</td>
</tr>';

        $selectTemplate = '<select name="freightType_###INDEX###">###OPTIONS###</select>';
        $optionTemplate = '<option value="###OPTIONVALUE###" ###SELECTED###>###OPTIONNAME###</option>';

        $optionsStringArray = array();
        $selectString = $vehicle['Freight']['Type'];
        if (isset($possibleCargos[$vehicle['Type']])) {
            $options = '';
            foreach ($possibleCargos[$vehicle['Type']] as $type) {
                if ($vehicle['Freight']['Type'] == $type) {
                    $selected = ' selected';
                } else {
                    $selected = '';
                }
                $optionValue = $type;
                $optionName = $type;
                $optionsStringArray[] = str_replace(
                    array('###OPTIONVALUE###', '###SELECTED###', '###OPTIONNAME###'),
                    array($optionValue, $selected, $optionName),
                    $optionTemplate
                );
            }
            $selectString = str_replace(
                array('###OPTIONS###', '###INDEX###'),
                array(implode('', $optionsStringArray), $cartIndex),
                $selectTemplate
            );
        }

        $exArr = array(
            $vehicle['Type'],
            strtoupper(strip_tags($vehicle['Name'])),
            strip_tags(trim($vehicle['Number']))
        );
        if (
            // trim out empty carts without number and without name
            $empty ||
            (strip_tags($vehicle['Name']) && trim($vehicle['Name']) != '.') ||
            (trim($vehicle['Number']) != '.' && trim($vehicle['Number']))
        ) {
            $exArr[] = $arithmeticHelper->nearestIndustry($vehicle['Location'], $data['Industries']);
            if ($vehicle['Tender']['Fuelamount']) {
                $exArr[] = 'firewood';
                $exArr[] = $vehicle['Tender']['Fuelamount'];
                $exArr[] = 'tenderamount_';
            } else {
                if ($vehicle['Freight']['Type']) {
                    $exArr[] = $selectString;
                    $exArr[] = $vehicle['Freight']['Amount'];
                    $exArr[] = 'freightamount_';
                } else {
                    $exArr[] = '-';
                    $exArr[] = '-';
                    $exArr[] = false;

                }

            }
            if (isset($exArr[6])) {
                $template = '<input size="2" maxlength="4" name="' . $exArr[6] . $cartIndex . '" value="' . $exArr[5] . '">';
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

        // label the industries
        if ($doSvg) {
            $svg .= '<text x="' . ($imx - (int)(($site['Location'][0] - $minX) / 100 * $scale) + $xoff) .
                '" y="' . ($imy - (int)(($site['Location'][1] - $minY) / 100 * $scale) + $yoff) . '" transform="rotate(' . $rotation .
                ',' . ($imx - (int)(($site['Location'][0] - $minX) / 100 * $scale) + $xoff) .
                ', ' . ($imy - (int)(($site['Location'][1] - $minY) / 100 * $scale) + $yoff) . ')" >' . $name . '</text>' . "\n";
        }

    }

    // create a "database" and store some infos about this file for the websies index page
    include_once 'database.php';
    addSave($NEWUPLOADEDFILE, $totalTrackLength, $totalSwitches, $totalLocos, $totalCarts, $maxSlope, getUserIpAddr());

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

    //echo "rendered in " . (microtime(true) - $start) . " microseconds\n";

    // store save for 5 minutes in case someone wants to edit the cart texts/numbers
    if (!isset($_POST['save'])) {
        rename('uploads/' . $NEWUPLOADEDFILE, 'saves/' . $NEWUPLOADEDFILE);
    }
}
//debug
// print_r($types);
