<?php

class Mapper
{

    var $data;
    private $imageWidth = 8000;

    /**
     * find min and max X and Y values in the save
     * whoever built track built it "somewhere"....
     *
     * initially to scale the network - but was skipped after getting the high quality backgrounds
     */
    private $minX = 0;
    private $maxX = 0;
    private $minY = 0;
    private $maxY = 0;
    private $scale;
    private $switchRadius;
    private $engineRadius;
    private $imx;
    private $imy;
    private $turnTableRadius;
    private $totalTrackLength;
    private $maxSlope;
    private $totalSwitches;
    private $totalLocos;
    private $totalCarts;
    private $NEWUPLOADEDFILE;
    private $empty;
    private $arithmeticHelper;
    private $allLabels = array(array(0, 0));

    var $initialsTreeDown = 1750;
    var $prows = '';
    var $irows = '';

    /**
     * Mapper constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param $htmlSvg
     * @param $NEWUPLOADEDFILE
     * @param $empty
     * @param $arithmeticHelper
     * @return string
     */
    function gethtmlSVG(&$htmlSvg, $NEWUPLOADEDFILE, $empty, $arithmeticHelper)
    {
        $doSvg = true;
        $this->empty = $empty;
        $this->arithmeticHelper = $arithmeticHelper;
        $this->NEWUPLOADEDFILE = $NEWUPLOADEDFILE;

        $types = array();

        /**
         * since the 0,0 of the map is not like in an image the top left corner, we have to normalize the coordinates
         */
        $this->minX = -200000;
        $this->maxX = 200000;
        $this->minY = -200000;
        $this->maxY = 200000;

        $x = $this->maxX - $this->minX;
        $y = $this->maxY - $this->minY;

        /**
         * Now we need a factor to scale the ingame coordinates of the network to our 8000px image
         */

        $max = max($x, $y);
        $this->scale = ($this->imageWidth * 100 / $max);

        $this->switchRadius = (80 / 2.2107077) * $this->scale;              // (size an armlength of switches)
        $this->engineRadius = 6 * $this->scale;                             // (radius of locomotives and carts)

        $this->turnTableRadius = (10 / 2.2107077) * $this->scale;           // size of turntables

        $this->imx = (int)$x / 100 * $this->scale;
        $this->imy = (int)$y / 100 * $this->scale;


        // OK - Now lets draw a map
        $this->totalTrackLength = 0;
        $this->maxSlope = 0;

        $svg = $this->drawTracksAndBeds();

        $svg .= $this->drawSwitches();

        $svg .= $this->drawPlayers();

        $svg .= $this->drawIndustries();

        $svg .= $this->drawTurntables();

        $svg .= $this->drawRollingStocks($htmlSvg);

        $svg .= $this->populateInfo($htmlSvg);

        $types = array();
        // create a "database" and store some infos about this file for the websies index page

        $db = @unserialize(@file_get_contents('db.db'));
        $db[$NEWUPLOADEDFILE] = array(
            $this->totalTrackLength,
            $this->totalSwitches,
            $this->totalLocos,
            $this->totalCarts,
            $this->maxSlope,
            getUserIpAddr(),
            sizeof($this->data['Removed']['Vegetation'])
        );
        file_put_contents('db.db', serialize($db));


        /**
         * add Watertowers to the map
         */
        if (isset($this->data['Watertowers'])) {
            foreach ($this->data['Watertowers'] as $site) {
                $x = $this->imx - (int)(($site['Location'][0] - $this->minX) / 100 * $this->scale);
                $y = $this->imy - (int)(($site['Location'][1] - $this->minY) / 100 * $this->scale);
                if ($doSvg) {
                    $svg .= '<path transform="rotate('.round($site['Rotation'][1]).' '.$x.' '.$y.')" ';
                    $svg .= 'd=" M'.$x.','.$y.' m -5,-5 l10,0 l0,3 l3,0 l0,4 l-3,0 l0,3 l-10,0 z" ';
                    $svg .= 'fill="lightblue" stroke="black" stroke-width="1"/>'."\n";
                    $svg .= '<circle cx="'.$x.'" cy="'.$y.'" r="3" fill="blue" />';
                }
            }
        }


        /**
         * chart (Legende) of carts
         */
        $carts = array(
            'flatcar_logs' => array($this->engineRadius / 3, 'red'),
            'flatcar_cordwood' => array($this->engineRadius / 3 * 2, 'orange'),
            'flatcar_stakes' => array($this->engineRadius / 3 * 2, 'yellow'),
            'flatcar_hopper' => array($this->engineRadius / 3 * 2, 'brown'),
            'flatcar_tanker' => array($this->engineRadius / 3 * 2, 'grey'),
        );

        $cartsDrawn = 0;
        foreach ($carts as $cartType => $cart) {
            $x = $this->imageWidth / 4;
            $y = 100 + $cartsDrawn * 5 * ($this->engineRadius * 1.1) / 2;
            $rx = $this->engineRadius * 1.1 * 2;
            $ry = ($this->engineRadius * 1.1);
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

        $svg .= $this->drawReplantableTrees();

        return $svg;
    }


    /**
     * @return string
     */
    function drawReplantableTrees()
    {
        $svg = '';
        foreach ($this->data['Removed']['Vegetation'] as $index => $tree) {

            if (isset($_POST['firstTree']) && isset($_POST['userTree'])) {
                // all trees
            }
            if (!isset($_POST['firstTree']) && isset($_POST['userTree'])) {
                // >1767
                if ($index < $this->initialsTreeDown) continue;
            }
            if (isset($_POST['firstTree']) && !isset($_POST['userTree'])) {
                // <1767
                if ($index > $this->initialsTreeDown) continue;
            }
            if (!isset($_POST['firstTree']) && !isset($_POST['userTree'])) {
                continue;
            }

            $treeX = floor((200000 + $tree[0]) / 100000);
            $treeY = floor((200000 + $tree[1]) / 100000);
            $minDistanceToSomething = 80000000;
            if (isset($this->data['Segments'][$treeX][$treeY])) {
                foreach ($this->data['Segments'][$treeX][$treeY] as $segment) {
                    if ($segment['LocationCenter']['X'] < $tree[0] - 6000) {
                        continue;
                    }
                    if ($segment['LocationCenter']['X'] > $tree[0] + 6000) {
                        continue;
                    }
                    if ($segment['LocationCenter']['Y'] < $tree[1] - 6000) {
                        continue;
                    }
                    if ($segment['LocationCenter']['Y'] > $tree[1] + 6000) {
                        continue;
                    }
                    $minDistanceToSomething = min($minDistanceToSomething, $this->distance($tree, $segment['LocationCenter']));
                }
            }
            if ($minDistanceToSomething > 700) {
                if ($index < $this->initialsTreeDown) {
                    $color = 'orange';
                } else {
                    $color = 'green';
                }
                $x = ($this->imx - (int)(($tree[0] - $this->minX) / 100 * $this->scale));
                $y = ($this->imy - (int)(($tree[1] - $this->minY) / 100 * $this->scale));
                $svg .= sprintf('<circle cx="%d" cy="%d" r="6" stroke="darkgreen" stroke-width="2" fill="%s" />', $x, $y, $color);
            }
        }

        return $svg;
    }

    /**
     * @param $a
     * @param $b
     * @return float
     */
    function distance($a, $b)
    {

        return sqrt(pow($a[0] - $b['X'], 2) + pow($a[1] - $b['Y'], 2));
    }


    /**
     * @param $newLabel
     * @return int|mixed
     */
    function getDistanceToNearestLabel($newLabel)
    {
        $minDistance = 8000;
        foreach ($this->allLabels as $oldLabel)
            $minDistance = min(
                $minDistance,
                sqrt(
                    pow($newLabel[0] - $oldLabel[0], 2) +
                    pow($newLabel[1] - $oldLabel[1], 2)
                ));

        return $minDistance;
    }

    /**
     * @return string
     */
    function drawTracksAndBeds()
    {
        $doSvg = true;
        $svg = '';
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

        if (isset($_POST['metalOverWood']) && $_POST['metalOverWood'] == 'YES') {
            $order = array(
                '1' => array(15, 'darkkhaki'),                      // variable bank
                '2' => array(15, 'darkkhaki'),                      //  constant bank
                '5' => array(15, 'darkgrey'),                       // variable wall
                '6' => array(15, 'darkgrey'),                       // constant wall
                '3' => array(15, 'orange'),                         //  wooden bridge
                '7' => array(15, 'lightblue'),                      //  iron bridge
                '4' => array(3, 'black'),                          // trendle track
                '0' => array(3, 'black'),                          // track  darkkhaki, darkgrey,orange,blue,black
            );
        }


        // statistics for the webpage index
        $zeroLenthSegments = array();// for error collections if desired.
        /**
         * Loop the order array painting one type over the next
         */
        foreach ($order as $current => $optionsArr) {
            if (isset($this->data['Splines'])) {
//                $continue = false;
//                $last = false;
                foreach ($this->data['Splines'] as $spi => $spline) {
                    $type = $spline['Type'];
                    if ($type != $current) continue;
                    if (in_array($type, array(1, 2, 5, 6, 3, 7))) {
                        $segments = $spline['Segments'];
                        $path = '';
                        //'<path d="M 100 100 L 300 100 L 200 300 z" fill="red" stroke="blue" stroke-width="3" />'
                        foreach ($segments as $si=>$segment) {
                            if ($segment['Visible'] != 1) {
                                $tool = 'M';
                            } else {
                                $tool = 'L';
                            }
                            $xStart = ($this->imx - (int)(($segment['LocationStart']['X'] - $this->minX) / 100 * $this->scale));
                            $yStart = ($this->imy - (int)(($segment['LocationStart']['Y'] - $this->minY) / 100 * $this->scale));
                            $xEnd = ($this->imx - (int)(($segment['LocationEnd']['X'] - $this->minX) / 100 * $this->scale));
                            $yEnd = ($this->imy - (int)(($segment['LocationEnd']['Y'] - $this->minY) / 100 * $this->scale));
                            if (!$path) {
                                $path = '<path d="M ' . $xStart . ',' . $yStart . ' ';
                                $path .= $tool . ' ' . $xEnd . ',' . $yEnd . ' ';
                            } else {
                                $path .= $tool . ' ' . $xEnd . ',' . $yEnd . ' ';
                            }
//                            $last = $xEnd.$yEnd;
                        }
//                        if(isset($this->data['Splines'][$spi+1])){
//                            $test = ($this->imx - (int)(($this->data['Splines'][$spi+1]['Segments'][0]['LocationStart']['X'] - $this->minX) / 100 * $this->scale)).
//                            ($this->imy - (int)(($this->data['Splines'][$spi+1]['Segments'][0]['LocationStart']['Y'] - $this->minY) / 100 * $this->scale));
//                            if($last!=$test){
//                                $path .= '" stroke="' . $optionsArr[1] . '" stroke-width="' . $optionsArr[0] . '" fill="none"/>' . "\n";
//                                $continue = false;
//                                $last = false;
//                                $svg .= $path;
//                            } else {
//                                $continue = true;
//                            }
//                        } else {
//                            $svg .= $path;
//                        }
                        $path .= '" stroke="' . $optionsArr[1] . '" stroke-width="' . $optionsArr[0] . '" fill="none"/>' . "\n";
                        $svg .= $path;
                    } else {

                        if ($type != $current) continue;            // if this spline is not the current type, skip it
                        $segments = $spline['Segments'];

                        foreach ($segments as $segment) {
                            if ($segment['Visible'] != 1) continue; // skip invisible tracks

                            $xStart = ($this->imx - (int)(($segment['LocationStart']['X'] - $this->minX) / 100 * $this->scale));
                            $yStart = ($this->imy - (int)(($segment['LocationStart']['Y'] - $this->minY) / 100 * $this->scale));
                            $xEnd = ($this->imx - (int)(($segment['LocationEnd']['X'] - $this->minX) / 100 * $this->scale));
                            $yEnd = ($this->imy - (int)(($segment['LocationEnd']['Y'] - $this->minY) / 100 * $this->scale));
                            $xCenter = ($this->imx - (int)(($segment['LocationCenter']['X'] - $this->minX) / 100 * $this->scale));
                            $yCenter = ($this->imy - (int)(($segment['LocationCenter']['Y'] - $this->minY) / 100 * $this->scale));

                            if ($doSvg) {
                                $svg .= '<line x1="' . ($xStart) . '" y1="' . ($yStart) .
                                    '" x2="' . ($xEnd) . '" y2="' . ($yEnd) .
                                    '" stroke="' . $optionsArr[1] . '" stroke-width="' . $optionsArr[0] . '"/>' . "\n";
                            }


                            $distance = sqrt(
                                pow($segment['LocationEnd']['X'] - $segment['LocationStart']['X'], 2) +
                                pow($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y'], 2) +
                                pow($segment['LocationEnd']['Z'] - $segment['LocationStart']['Z'], 2)
                            );

                            if (in_array($type, array(4, 0))) {
                                $this->totalTrackLength += $distance;

                                $height = $segment['LocationEnd']['Z'] - $segment['LocationStart']['Z'];
                                $height = abs($height);
                                $length = sqrt(pow($segment['LocationEnd']['X'] - $segment['LocationStart']['X'], 2) +
                                    pow($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y'], 2));


                                if (empty($length)) {//check for zero length tracks
                                    $zeroLenthSegments[] = $segment;
                                    if ($doSvg) { //@ToDo make function later.
                                        $svg .= sprintf('<circle cx="%d" cy="%d" r="10" stroke="red" stroke-width="2" fill="red" />', $xCenter, $yCenter);
                                    }
                                    continue; //This may cause issues down the road. We may need to stop at this point and return the errors segment.
                                } else {
                                    $slope = ($height * 100 / $length);
                                }

                                if ($slope > $this->maxSlope) {
                                    $slopecoords = array($xCenter, $yCenter);
                                }
                                $this->maxSlope = max($this->maxSlope, $slope);
                            }

// label some splines with their slope - not yet working
// main problem: find a spot for the text that is near to track but do not override other stuff
                            if (!isset($_POST['slopeTrigger'])) $_POST['slopeTrigger'] = 2;
                            if (!isset($_POST['slopeTriggerPrefix'])) $_POST['slopeTriggerPrefix'] = '..';
                            if (!isset($_POST['slopeTriggerDecimals'])) $_POST['slopeTriggerDecimals'] = 1;
                            if ($distance > 0 && in_array($type, array(4, 0))) {
                                if (abs($slope) > $_POST['slopeTrigger']) {
                                    $tanA = (
                                        ($segment['LocationEnd']['Y'] - $segment['LocationStart']['Y']) /
                                        max(0.00001, ($segment['LocationEnd']['X'] - $segment['LocationStart']['X']))
                                    );
                                    $a = rad2deg(atan($tanA));
                                    if ($a > 0) {
                                        $a -= 90;
                                    } else {
                                        $a += 90;
                                    }


                                    if ($this->getDistanceToNearestLabel(array($xCenter, $yCenter)) > 60) {
                                        $this->allLabels[] = array($xCenter, $yCenter);
                                        $svg .= '<text x="' . $xCenter . '" y="' . $yCenter . '" transform="rotate(' . $a .
                                            ',' . $xCenter . ', ' . $yCenter . ')">' . $_POST['slopeTriggerPrefix'] . round($slope, $_POST['slopeTriggerDecimals']) . '%</text>' . "\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
//print_r($slopecoords);
        if (isset($_POST['maxslope']) && $_POST['maxslope'] && isset($slopecoords)) {
            $svg .= '<circle cx="' . $slopecoords[0] . '" cy="' . $slopecoords[1] . '" r="' . ($this->turnTableRadius * 5) . '" stroke="orange" stroke-width="5" fill="none"/>' . "\n";
            $svg .= '<circle cx="' . $slopecoords[0] . '" cy="' . $slopecoords[1] . '" r="' . ($this->turnTableRadius * 4) . '" stroke="orange" stroke-width="5" fill="none"/>' . "\n";
            $svg .= '<circle cx="' . $slopecoords[0] . '" cy="' . $slopecoords[1] . '" r="' . ($this->turnTableRadius * 3) . '" stroke="orange" stroke-width="5" fill="none"/>' . "\n";
            $svg .= '<circle cx="' . $slopecoords[0] . '" cy="' . $slopecoords[1] . '" r="' . ($this->turnTableRadius * 2) . '" stroke="orange" stroke-width="5" fill="none"/>' . "\n";
        }

        return $svg;

    }

    /**
     * @return string
     */
    public function drawSwitches()
    {
        $doSvg = true;
        $svg = '';
        /**
         * Fill in the missing gaps AKA switches
         */
        $types = array();
        if (!isset($this->data['Switchs'])) return '';
        foreach ($this->data['Switchs'] as $switch) {
            $this->totalSwitches++;
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
            $segments = $switch['Location'];
            // fix given angles and convert to radiant - subtract 90 - because ingame coordinates do not point NORTH (!?)
            $rotation = deg2rad($switch['Rotation'][1] - 90);
            $rotSide = deg2rad($switch['Rotation'][1] - 90 + $dir);
            $rotCross = deg2rad($switch['Rotation'][1] + 180);

            if ($doSvg) {
                $x = ($this->imx - (int)(($switch['Location'][0] - $this->minX) / 100 * $this->scale));
                $y = ($this->imy - (int)(($switch['Location'][1] - $this->minY) / 100 * $this->scale));
                if ($dir == 99) { //CROSS
                    $crosslength = $this->switchRadius / 10;

                    $x2 = ($this->imx - (int)(($switch['Location'][0] - $this->minX) / 100 * $this->scale) + (cos($rotCross) * $crosslength));
                    $y2 = ($this->imy - (int)(($switch['Location'][1] - $this->minY) / 100 * $this->scale) + (sin($rotCross) * $crosslength));

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
                    $xStraight = ($this->imx - (int)(($switch['Location'][0] - $this->minX) / 100 * $this->scale) + (cos($rotation) * $this->switchRadius / 2));
                    $yStraight = ($this->imy - (int)(($switch['Location'][1] - $this->minY) / 100 * $this->scale) + (sin($rotation) * $this->switchRadius / 2));
                    $xSide = ($this->imx - (int)(($switch['Location'][0] - $this->minX) / 100 * $this->scale) + (cos($rotSide) * $this->switchRadius / 2));
                    $ySide = ($this->imy - (int)(($switch['Location'][1] - $this->minY) / 100 * $this->scale) + (sin($rotSide) * $this->switchRadius / 2));

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

        return $svg;
    }

    /**
     * @return string
     */
    public function drawTurntables()
    {
        $doSvg = true;
        $svg = '';
        /**
         * Fill in more missing gaps AKA turntables
         */
        if (!isset($this->data['Turntables'])) {
            $this->data['Turntables'] = array();
        }
        foreach ($this->data['Turntables'] as $table) {
            $type = trim($table['Type']);
            /**
             * 0 = regular
             * 1 = light and nice
             */

            // fix given angles and convert to radiant - subtract 90 - because ingame coordinates do not point NORTH (!?)
            $rotation = deg2rad($table['Rotator'][1] + 90);
            $rotation2 = deg2rad($table['Rotator'][1] + 90 - $table['Deck'][1]);

            if ($doSvg) {
                $this->turnTableRadius = 25;

                $x = ($this->imx - (int)(($table['Location'][0] - $this->minX) / 100 * $this->scale));
                $y = ($this->imx - (int)(($table['Location'][1] - $this->minX) / 100 * $this->scale));
                $x2 = ($this->imx - (int)(($table['Location'][0] - $this->minX) / 100 * $this->scale) + (cos($rotation) * $this->turnTableRadius));
                $y2 = ($this->imy - (int)(($table['Location'][1] - $this->minY) / 100 * $this->scale) + (sin($rotation) * $this->turnTableRadius));

                $cx = $x + ($x2 - $x) / 2;
                $cy = $y + ($y2 - $y) / 2;

                $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . ($this->turnTableRadius / 2) . '" stroke="black" stroke-width="1" fill="lightyellow" />' . "\n";

                $svg .= '<line x1="' . ($cx - (cos($rotation2) * $this->turnTableRadius / 2)) .
                    '" y1="' . ($cy - (sin($rotation2) * $this->turnTableRadius / 2))
                    . '" x2="' . ($cx + (cos($rotation2) * $this->turnTableRadius / 2)) .
                    '" y2="' . ($cy + (sin($rotation2) * $this->turnTableRadius / 2))
                    . '" stroke="black" stroke-width="3"/>' . "\n";

            }
        }

        return $svg;
    }

    /**
     * @param $htmlSvg
     * @return string
     */
    public function drawRollingStocks(&$htmlSvg)
    {
        $doSvg = true;
        $svg = '';
        /**
         * draw some vehicles on top of that image
         * define size and color of vehicle here
         */

        $cartColors = array(
            'handcar' => array($this->engineRadius, 'white'),
            'porter_040' => array($this->engineRadius, 'black'),
            'porter_042' => array($this->engineRadius, 'black'),
            'eureka' => array($this->engineRadius, 'black'),
            'eureka_tender' => array($this->engineRadius, 'black'),
            'climax' => array($this->engineRadius, 'black'),
            'heisler' => array($this->engineRadius, 'black'),
            'class70' => array($this->engineRadius, 'black'),
            'class70_tender' => array($this->engineRadius, 'black'),
            'cooke260' => array($this->engineRadius, 'black'),
            'cooke260_tender' => array($this->engineRadius, 'black'),
            'flatcar_logs' => array($this->engineRadius / 3, 'red'),
            'flatcar_cordwood' => array($this->engineRadius / 3 * 2, 'orange'),
            'flatcar_stakes' => array($this->engineRadius / 3 * 2, 'yellow'),
            'flatcar_hopper' => array($this->engineRadius / 3 * 2, 'brown'),
            'boxcar' => array($this->engineRadius / 3 * 2, 'purple'),
            'flatcar_tanker' => array($this->engineRadius / 3 * 2, 'grey'),
        );

// build some extra HTML for a form to edit cart data

        $cartExtraStr = '
        <br/>
        <h4>Rolling Stock</h4>
        <form method="POST" action="../converter.php"><input type="hidden" name="save" value="' . $this->NEWUPLOADEDFILE . '">
        <table class="export__mapper">
        <tr>
        <th>Type</th>
        <th>Name</th>
        <th>Number</th>
        <th>near</th>
        <th>Cargo</th>
        <th>Amount</th>
        </tr>
        ###TROWS###</table><br/>
        <button class="button">Apply Rolling Stock changes</button></form>

        <br/><br/>
        <h4>Trees</h4>
        <form method="POST" action="../converter.php"><input type="hidden" name="save" value="' . $this->NEWUPLOADEDFILE . '">
        <input name="replant" value="YES" type="hidden" />
        <button class="button">Replant Trees</button></form>

        <br/><br/>
        <h4>Players</h4>
        <form method="POST" action="../converter.php"><input type="hidden" name="save" value="' . $this->NEWUPLOADEDFILE . '">
        <table class="export__mapper">
        <tr>
        <th>Player</th>
        <th>XP</th>
        <th>Money</th>
        <th>near</th>
        <th>Delete</th>
        </tr>
        ' . $this->prows . '</table><br/>
        <button class="button">Apply Player Changes</button></form>

        <br/><br/>
        <h4>Industries</h4>
        <form method="POST" action="../converter.php"><input type="hidden" name="save" value="' . $this->NEWUPLOADEDFILE . '">
        <table class="export__mapper">
        <tr>
        <th>Industry</th>
        <th>Item 1</th>
        <th>Item 2</th>
        <th>Item 3</th>
        <th>Item 4</th>
        </tr>
        ' . $this->irows . '</table><br/>
        <button class="button">Apply Industry Changes</button></form>

        <br/><br/>
        <h4>Carts</h4>
        <form method="POST" action="../converter.php"><input type="hidden" name="save" value="' . $this->NEWUPLOADEDFILE . '">
        <table class="export__mapper">
        ###UMROWS###
        </table>
        <br/>
        <button class="button">Get Carts from Underground</button></form>
        ';
        $trows = $umrows = '';

        // later you can switch cargo on carts - maybe this can be done by editing the save via the mapper later?
        $possibleCargos = array(
            'flatcar_logs' => array('log'),
            'flatcar_stakes' => array('rail', 'lumber', 'beam', 'rawiron'),
            'flatcar_hopper' => array('ironore', 'coal'),
            'flatcar_cordwood' => array('cordwood'),
        );

        $cargoNames = array(
            "log" => "Logs",
            "cordwood" => "Cordwood",
            "beam" => "Beams",
            "lumber" => "Lumber",
            "ironore" => "Iron Ore",
            "rail" => "Rails",
            "rawiron" => "Raw Iron",
            "coal" => "Coal",
            "steelpipe" => "Steel Pipes",
            "crate_tools" => "Tools",
            "crudeoil" => "Crude Oil",
            "oilbarrel" => "Oil Barrels",
        );


        foreach ($this->data['Frames'] as $cartIndex => $vehicle) {
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
            if (!isset($vehicle['Freight'])) {
                $vehicle['Freight']['Type'] = '';
            }
            if (!isset($vehicle['Name'])) {
                $vehicle['Name'] = '';
            }
            if (!isset($vehicle['Number'])) {
                $vehicle['Number'] = '';
            }
            if (!isset($vehicle['Tender'])) {
                $vehicle['Tender']['Fuelamount'] = '';
            }

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
                        array($optionValue, $selected, $cargoNames[$optionName]),
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
                strtoupper(strip_tags(trim($vehicle['Name']))),
                strip_tags(trim($vehicle['Number']))
            );
            if (
                // trim out empty carts without number and without name
                $this->empty ||
                (strip_tags($vehicle['Name']) && trim($vehicle['Name']) != '.') ||
                (trim($vehicle['Number']) != '.' && trim($vehicle['Number']))
            ) {
                $exArr[] = $this->arithmeticHelper->nearestIndustry($vehicle['Location'], $this->data['Industries']);
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

            $x = ($this->imx - (int)(($vehicle['Location'][0] - $this->minX) / 100 * $this->scale));
            $y = ($this->imy - (int)(($vehicle['Location'][1] - $this->minY) / 100 * $this->scale));
            if ($doSvg) {

                if (
                    $vehicle['Type'] == 'porter_040'
                    || $vehicle['Type'] == 'porter_042'
//                    || $vehicle['Type'] == 'handcar'
                    || $vehicle['Type'] == 'eureka'
                    || $vehicle['Type'] == 'climax'
                    || $vehicle['Type'] == 'heisler'
                    || $vehicle['Type'] == 'class70'
                    || $vehicle['Type'] == 'cooke260'
                ) {
                    $yl=($this->engineRadius / 3)*2;
                    $xl=($this->engineRadius / 2)*2;

                    $svg .= '<path transform="rotate('.round($vehicle['Rotation'][1]).' '.$x.' '.$y.')" ';
                    $svg .= 'd="M'.($x-($this->engineRadius / 2)).','.$y;
                    $svg .= ' l '.($xl/3).','.($yl/2).' l '.($xl/3*2).',0 l 0,-'.$yl;
                    $svg .= ' l -'.($xl/3*2).',0 z" fill="purple" stroke="black" stroke-width="2"/>'."\n";
                } else {

                    $yl=($this->engineRadius / 3)*2;
                    $xl=$this->engineRadius;

                    if(strpos(strtolower($vehicle['Type']), 'tender')){
                        $xl=$xl/3*2;
                    }


                    $svg.= '<path d="M'.round($x).','.round($y).' m-'.(($xl / 2)).',-'.
                        (($yl / 2) ).' h'.($xl -4)
                        .' a2,2 0 0 1 2,2 v'.($yl -4)
                        .' a2,2 0 0 1 -2,2 h-'.($xl -4)
                        .' a2,2 0 0 1 -2,-2 v-'.($yl -4)
                        .' a2,2 0 0 1 2,-2 z" fill="'.$cartColors[$vehicle['Type']][1].'" stroke="black" stroke-width="1" 
                        transform="rotate(' . round($vehicle['Rotation'][1]) .
                        ', ' . round($x) .
                        ', ' . round($y) . ')"
                        />';


//                    $svg .= '<ellipse cx="' . $x . '" cy="' . $y . '" rx="' . ($this->engineRadius / 2) . '" ry="' . ($this->engineRadius / 3) .
//                        '" style="fill:' . $cartColors[$vehicle['Type']][1] . ';stroke:black;stroke-width:1" transform="rotate(' . $vehicle['Rotation'][1] .
//                        ', ' . ($this->imx - (int)(($vehicle['Location'][0] - $this->minX) / 100 * $this->scale)) . ', ' . ($this->imy - (int)(($vehicle['Location'][1] - $this->minY) / 100 * $this->scale)) . ')"
//              />';
                }


                if ($vehicle['Location'][2] < 1000) {
                    $svg .= '<ellipse cx="' . $x . '" cy="' . $y . '" rx="' . (($this->engineRadius / 2) * 10) .
                        '" ry="' . (($this->engineRadius / 2) * 10) .
                        '" style="fill:none;stroke:red;stroke-width:10" transform="rotate(' . $vehicle['Rotation'][1] .
                        ', ' . ($this->imx - (int)(($vehicle['Location'][0] - $this->minX) / 100 * $this->scale)) . ', ' . ($this->imy - (int)(($vehicle['Location'][1] - $this->minY) / 100 * $this->scale)) . ')"
              />';
                    $svg .= '<text x="' . $x . '" y="' . $y . '" >' . '&nbsp;&nbsp;' . $vehicle['Location'][2] . '</text>' . "\n";

                    $umrows .= '<input name="underground_' . $cartIndex . '" value="1" type="hidden">';
                }
            }

            // add some names to the locomotives
            if (
                $vehicle['Type'] == 'porter_040'

                || $vehicle['Type'] == 'porter_042'
//                || $vehicle['Type'] == 'handcar'
                || $vehicle['Type'] == 'eureka'
                || $vehicle['Type'] == 'climax'
                || $vehicle['Type'] == 'heisler'
                || $vehicle['Type'] == 'class70'
                || $vehicle['Type'] == 'cooke260'
            ) {
                $this->totalLocos++;
                $name = strtoupper(strip_tags($vehicle['Name']));
                // fallback to engine type when no name was given
                if (!$name) {
                    $name = ucfirst($vehicle['Type']);
                }
                //$name.=' ('.$vehicle['Location']['Z'].')';
                // label locomotives

                $textRotation = $vehicle['Rotation'][1];
                if ($textRotation < 0) {
                    $textRotation += 90;
                } else {
                    $textRotation -= 90;
                }

                if ($doSvg) {
                    $this->allLabels[] = array($x, $y);
                    $svg .= '<text x="' . $x . '" y="' . $y .
                        '" transform="rotate(' . $textRotation . ', ' . $x . ', ' .
                        $y . ')"  stroke="black" fill="white" font-size="1em" >' .
                        '..' . $name . '</text>' . "\n";

                }
            } else {
                if($vehicle['Type']!='handcar'){
                    $this->totalCarts++;
                }
            }

        }

        $images = array(
            '>porter_040' => '><img src="../images/porter.png">',
            '>porter_042' => '><img src="../images/porter2.png">',
            '>handcar' => '><img src="../images/handcar.png">',
            '>eureka_tender' => '><img src="../images/eureka_tender.png">',
            '>class70_tender' => '><img src="../images/class70_tender.png">',
            '>cooke260_tender' => '><img src="../images/cooke_tender.png">',
            '>eureka' => '><img src="../images/eureka.png">',
            '>climax' => '><img src="../images/climax.png">',
            '>heisler' => '><img src="../images/heisler.png">',
            '>class70' => '><img src="../images/class70.png">',
            '>cooke260' => '><img src="../images/cooke.png">',
            '>boxcar' => '><img src="../images/boxcar.png">',
            '>flatcar_cordwood' => '><img src="../images/flatcar_cordwood.png">',
            '>flatcar_logs' => '><img src="../images/flatcar_logs.png">',
            '>flatcar_stakes' => '><img src="../images/flatcar_stakes.png">',
            '>flatcar_tanker' => '><img src="../images/flatcar_tanker.png">',
            '>flatcar_hopper' => '><img src="../images/flatcar_hopper.png">',
        );

        $cartExtraStr = str_replace('###UMROWS###', $umrows, $cartExtraStr);
        $cartExtraStr = str_replace('###TROWS###', $trows, $cartExtraStr);
        $cartExtraStr = str_replace(array_keys($images), $images, $cartExtraStr);
        $htmlSvg = str_replace('###EXTRAS###', $cartExtraStr, $htmlSvg);

        return $svg;
    }

    /**
     * @param $htmlSvg
     * @return string
     */
    public function populateInfo(&$htmlSvg)
    {
        $svg = '';
        /**
         * add player info
         */

        $text = "";
        $text2 = "";
        $playerInfo = "";
        if ($this->data['Players'][0]['Name'] == 'ian76g') {
            $this->data['Players'][0]['Money'] -= 30000;
        }

        foreach ($this->data['Players'] as $player) {
            $text .= str_pad($player['Name'], 20, ' ', STR_PAD_BOTH) . "\n";
            $text2 .= ' (XP ' . str_pad($player['Xp'], 7, ' ', STR_PAD_RIGHT) . '  ' .
                str_pad($player['Money'], 8, ' ', STR_PAD_LEFT) . '$)' . "\n\n";
        }

        $textlines = explode("\n", $text);
        $playerInfo .= '<div>';
        foreach ($textlines as $textline) {
            $playerInfo .= '<p>' . $textline . '</p>';
        }
        $playerInfo .= '</div><div>';

        $textlines = explode("\n", $text2);
        foreach ($textlines as $textline) {
            $playerInfo .= '<p>' . $textline . '</p>';
        }
        $playerInfo .= '</div>';

        $htmlSvg = str_replace('###PLAYERINFO###', $playerInfo, $htmlSvg);
        return $svg;
    }

    /**
     * @return string
     */
    function drawPlayers()
    {
        /**
         * add player info to the map
         */
        $prows = '';
        $svg = '';
        $text = '===== PLAYERS ON THIS SERVER =====' . "\n\n\n";
        $text2 = '.' . "\n\n\n";
        if ($this->data['Players'][0]['Name'] == 'ian76g') {
            $this->data['Players'][0]['Money'] -= 30000;
        }
        foreach ($this->data['Players'] as $playerIndex => $player) {
            $industry = $this->arithmeticHelper->nearestIndustry($player['Location'], $this->data['Industries']);
            $prows .= '<tr>
                <td>' . $player['Name'] . '</td>
                <td><input size="5" maxlength="15" name="xp_' . $playerIndex . '" value="' . $player['Xp'] . '"></td>
                <td><input size="5" maxlength="15" name="money_' . $playerIndex . '" value="' . $player['Money'] . '"></td>
                <td>' . $industry . '</td>
                <td><input name="deletePlayer_' . $playerIndex . '" type="checkbox"></td>
                </tr>';
        }

        //     $text .= str_pad($player['Name'], 20, ' ', STR_PAD_BOTH) . "\n\n";
        //     $text2 .= ' (XP ' . str_pad($player['Xp'], 7, ' ', STR_PAD_RIGHT) . '  ' .
        //         str_pad($player['Money'], 8, ' ', STR_PAD_LEFT) . '$)' . "\n\n";
        // }
        // $svg .= '<text x="50" y="50" font-size="20" dy="0">';
        // $textlines = explode("\n", $text);
        // foreach ($textlines as $textline) {
        //     $svg .= '<tspan x="50" dy="1.2em">' . $textline . '&nbsp;</tspan>';
        // }
        // $svg .= '</text>' . "\n";

        // $svg .= '<text x="350" y="50" font-size="20" dy="0">';
        // $textlines = explode("\n", $text2);
        // foreach ($textlines as $textline) {
        //     $svg .= '<tspan x="350" dy="1.2em">' . $textline . '&nbsp;</tspan>';
        // }
        // $svg .= '</text>' . "\n";

        $this->prows = $prows;

        return $svg;
    }

    /**
     * @return string
     */
    function drawIndustries()
    {
        $svg = '';
        $irows = '';
        /**
         * add the industries to the map
         */
        foreach ($this->data['Industries'] as $number => $site) {

            /**
             * again - fix some issues with wrong INPUT from json
             */
            $pis = array();
            $pos = array();
            $xoff = $yoff = 0;
            switch ($site['Type']) {
                case '1':
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    $pos = array('logs_p.svg', 'cordwood_p.svg', 'cordwood_p.svg', 'logs_p.svg');
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
                    $pos = array('lumber_p.svg', 'beams_p.svg');
                    $pis = array('logs_p.svg');
//                    $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                    $xoff = -35;
                    $yoff = 15;
                    $rotation = 0;
                    break;
                case '3':
                    $name = 'Smelter';
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    $pos = array('iron_p.svg', 'rails_p.svg');
                    $pis = array('ironore_p.svg', 'cordwood_p.svg');
//                    $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                    $rotation = 90;
                    break;
                case '4':
                    $name = 'Ironworks';
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    $pos = array('pipes_p.svg', 'tools_p.svg');
                    $pis = array('iron_p.svg', 'coal_p.svg');
//                    $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                    $rotation = 90;
                    break;
                case '5':
                    $name = 'Oilfield';
                    array_pop($site['EductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    $pis = array('pipes_p.svg', 'beams_p.svg', 'tools_p.svg');
                    $pos = array('oil_p.svg');
//                    $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                    $rotation = 0;
                    break;
                case '6':
                    $name = 'Refinery';
                    array_pop($site['EductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    $pis = array('oil_p.svg', 'pipes_p.svg', 'lumber_p.svg');
                    $pos = array('barrels_p.svg', 'barrels_p.svg');
//                    $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                    $rotation = 0;
                    break;
                case '7':
                    $name = 'Coal Mine';
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    $pis = array('beams_p.svg', 'rails_p.svg');
                    $pos = array('coal_p.svg');
//                    $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
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
                    $pis = array('lumber_p.svg', 'beams_p.svg');
                    $pos = array('ironore_p.svg');
//                    $name .= "\nI:" . implode(',', $site['EductsStored']) . "\nO:" . implode(',', $site['ProductsStored']);
                    $rotation = 45;
                    $yoff = +50;
                    $xoff = -20;
                    break;
                case '9':
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    array_pop($site['ProductsStored']);
                    $name = 'Freight Depot';
                    $rotation = 90;
                    break;
                case '10':
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    array_pop($site['EductsStored']);
                    $pis = array('cordwood_p.svg');
                    $name = 'F';
                    $name .= "#$number";
                    $rotation = ($site['Rotation'][1] > 0) ? ($site['Rotation'][1] - 90) : ($site['Rotation'][1] + 90);
                    $x = ($this->imx - (int)(($site['Location'][0] - $this->minX) / 100 * $this->scale));
                    $y = ($this->imy - (int)(($site['Location'][1] - $this->minY) / 100 * $this->scale));
                    $svg .= '<path transform="rotate('.round($site['Rotation'][1]).' '.$x.' '.$y.')" ';
                    $svg .= 'd="M'.$x.','.$y.' m-18,-15 l10,0 l0,30 l-10,0 z" fill="orange" stroke="brown" />'."\n";
                    $xoff = -20;
                    $yoff=0;
                    break;
                default:
                    die('unknown industry');
            }

            $educts = sizeof($site['EductsStored']);
            if ($educts) {
                $irows .= '<tr class="export__educts"> <td>' . $name . ' Educts</td>';
                for ($i = 0; $i < $educts; $i++) {
                    $irows .= '<td><input style="width:30px;vertical-align:middle;" size="2" maxlength="3" name="educt' . $i . '_' . $number . '" value="' . $site['EductsStored'][$i] . '">';
                    if (isset($pis[$i])) {
                        $irows .= '<img style="float:right" src="' . WWW_ROOT . 'images/' . $pis[$i] . '" height="30" />';
                    }
                    $irows .= '</td>';
                }
                for ($i = $educts; $i < 4; $i++) {
                    $irows .= '<td>&nbsp;</td>';
                }
                $irows .= '</tr>';
            }
            $educts = sizeof($site['ProductsStored']);
            $irows .= '<tr class="export__products"> <td>' . $name . ' Products</td>';
            for ($i = 0; $i < $educts; $i++) {
                $irows .= '<td><input style="width:30px;vertical-align:middle;" size="2" maxlength="3" name="product' . $i . '_' . $number . '" value="' . $site['ProductsStored'][$i] . '"> ';
                if (isset($pos[$i])) {
                    $irows .= '<img style="float:right" src="' . WWW_ROOT . 'images/' . $pos[$i] . '" height="30" />';
                }
                $irows .= ' </td>';
            }
            for ($i = $educts; $i < 4; $i++) {
                $irows .= '<td>&nbsp;</td>';
            }
            $irows .= '</tr>';

            // label the industries
            $svg .= '<text x="' . ($this->imx - (int)(($site['Location'][0] - $this->minX) / 100 * $this->scale) + $xoff) .
                '" y="' . ($this->imy - (int)(($site['Location'][1] - $this->minY) / 100 * $this->scale) + $yoff) . '" transform="rotate(' . $rotation .
                ',' . ($this->imx - (int)(($site['Location'][0] - $this->minX) / 100 * $this->scale)) .
                ', ' . ($this->imy - (int)(($site['Location'][1] - $this->minY) / 100 * $this->scale)) . ')" >' . $name . '</text>' . "\n";
        }

        $this->irows = $irows;

        return $svg;

    }

}
