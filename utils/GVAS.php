<?php
include_once 'types.php';
include_once 'database.php';

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
                if (trim($object->NAME) == 'FrameNameArray') {
                    foreach ($object->CONTENTOBJECTS as $co) {
                        if (is_object($co) && trim($co->NAME) == 'STRING') {
                            if ($co->ARRCOUNTER)
                                //echo "(".$co->string.")";
                                if (
                                    ($co->ARRCOUNTER !== '') &&
                                    isset($_POST['name_' . $co->ARRCOUNTER]) &&
                                    $_POST['name_' . $co->ARRCOUNTER] != trim($co->string)
                                ) {
                                    $co->string = strip_tags(trim($_POST['name_' . $co->ARRCOUNTER])) . hex2bin('00');
                                }
                        }
                    }
                }
                if (trim($object->NAME) == 'FreightAmountArray') {
                    foreach ($object->CONTENTOBJECTS as $co) {
                        if (is_object($co) && trim($co->NAME) == 'IntProperty') {
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
                        if (is_object($co) && trim($co->NAME) == 'FloatProperty') {
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
                if (trim($object->NAME) == 'FreightTypeArray') {
                    foreach ($object->CONTENTOBJECTS as $co) {
                        if (is_object($co) && $co->ARRCOUNTER) {
                            if (
                                ($co->ARRCOUNTER !== '') &&
                                isset($_POST['freightType_' . $co->ARRCOUNTER]) &&
                                $_POST['freightType_' . $co->ARRCOUNTER] != trim($co->string)
                            ) {
                                $co->string = strip_tags(trim($_POST['freightType_' . $co->ARRCOUNTER])) . hex2bin('00');
                            }
                        }
                    }
                }

                $output .= $object->serialize();
            } else {
                die('WHOPSI');
            }
        }
        $output .= hex2bin('050000004e6f6e650000000000');

        if (isset($_POST['save'])) {
            $save = getSave($this->NEWUPLOADEDFILE);

            if (getUserIpAddr() != $save['ip_address']) {
                die("This does not seem to be your save file.");
            }
            echo "SAVING FILE " . $this->NEWUPLOADEDFILE . '.modified' . "<br>\n";
            file_put_contents('saves/' . $this->NEWUPLOADEDFILE . '.modified', $output);
            echo '<a href="saves/' . $this->NEWUPLOADEDFILE . '.modified' . '">Download your modified save here </A><br>';
            echo 'Want to upload this map again? <a href="upload.php">Add your renumbered save again</a><br>';
        } else {
            if ($againAllowed) {
                //echo "RESAVING FILE TO DISK - EMPTY NUMBERS BECAME A DOT " . $this->NEWUPLOADEDFILE . "<br>\n";
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
