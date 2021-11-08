<?php

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


        $silverPlate = array();

        $keys = array('Player', 'Freight', 'Compressor', 'Tender', 'Coupler', 'Boiler', 'Headlight', 'Frame', 'Watertower', 'Switch');
        foreach ($keys as $key) {
            $silverPlate[$key . 's'] = array();
            if (isset($this->goldenBucket[$key])) {
                foreach ($this->goldenBucket[$key] as $index => $value) {
                    foreach ($value as $idx => $v) {
                        $silverPlate[$key . 's'][$idx][$index] = $v;
                    }
                }
                unset($this->goldenBucket[$key]);
                $this->goldenBucket[$key . 's'] = $silverPlate[$key . 's'];
            }
        }

        foreach ($this->goldenBucket['Frames'] as $i => $frame) {
            if(isset($this->goldenBucket['Boilers'][$i]))
            $this->goldenBucket['Frames'][$i]['Boiler'] = $this->goldenBucket['Boilers'][$i];

            if(isset($this->goldenBucket['Headlights'][$i]))
            $this->goldenBucket['Frames'][$i]['Headlights'] = $this->goldenBucket['Headlights'][$i];

            if(isset($this->goldenBucket['Freights'][$i]))
            $this->goldenBucket['Frames'][$i]['Freight'] = $this->goldenBucket['Freights'][$i];

            if(isset($this->goldenBucket['Compressors'][$i]))
            $this->goldenBucket['Frames'][$i]['Compressor'] = $this->goldenBucket['Compressors'][$i];

            if(isset($this->goldenBucket['Tenders'][$i]))
            $this->goldenBucket['Frames'][$i]['Tender'] = $this->goldenBucket['Tenders'][$i];

            if(isset($this->goldenBucket['Couplers'][$i]))
            $this->goldenBucket['Frames'][$i]['Coupler'] = $this->goldenBucket['Couplers'][$i];

            if(isset($this->goldenBucket['Regulatorvalue'][$i]))
            $this->goldenBucket['Frames'][$i]['Regulator'] = $this->goldenBucket['Regulatorvalue'][$i];

            if(isset($this->goldenBucket['Brakevalue'][$i]))
            $this->goldenBucket['Frames'][$i]['Brake'] = $this->goldenBucket['Brakevalue'][$i];

            if(isset($this->goldenBucket['Reverservalue'][$i]))
            $this->goldenBucket['Frames'][$i]['Reverser'] = $this->goldenBucket['Reverservalue'][$i];

            if(isset($this->goldenBucket['Reverservalue'][$i]))
            $this->goldenBucket['Frames'][$i]['Smokestack'] = $this->goldenBucket['Smokestacktype'][$i];

            if(isset($this->goldenBucket['Reverservalue'][$i]))
            $this->goldenBucket['Frames'][$i]['Generatorvalvevalue'] = $this->goldenBucket['Generatorvalvevalue'][$i];

            if(isset($this->goldenBucket['Marker'][$i]))
            $this->goldenBucket['Frames'][$i]['Marker']['Front']['Right'] = $this->goldenBucket['Marker']['Lights']['Front']['Right'][$i];

            if(isset($this->goldenBucket['Marker'][$i]))
            $this->goldenBucket['Frames'][$i]['Marker']['Front']['Left'] = $this->goldenBucket['Marker']['Lights']['Front']['Left'][$i];

            if(isset($this->goldenBucket['Marker'][$i]))
            $this->goldenBucket['Frames'][$i]['Marker']['Rear']['Right'] = $this->goldenBucket['Marker']['Lights']['Rear']['Right'][$i];

            if(isset($this->goldenBucket['Marker'][$i]))
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
                    'LocationCenter' => array(
                        'X' => $startLocs[0] + ($endLocs[0] - $startLocs[0]) / 2,
                        'Y' => $startLocs[1] + ($endLocs[1] - $startLocs[1]) / 2,
                        'Z' => $startLocs[2] + ($endLocs[2] - $startLocs[2]) / 2
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

        /**
         * HANDLE DATA MANIPULATION AND SAVE FILE
         */

        $tmp = $this->handleEditAndSave($againAllowed);
        if ($tmp == 'AGAIN') {
            return $tmp;
        }

        $json = json_encode($this->goldenBucket, JSON_PRETTY_PRINT);
        file_put_contents('xx.json', $json);
        return $json;

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
     * @param $againAllowed
     * @return string
     */
    function handleEditAndSave($againAllowed)
    {
        $output = '';
        foreach ($this->saveObject['objects'] as $saveObjectIndex => $object) {
            if (is_object($object)) {
                if (false && trim($object->NAME) == 'RemovedVegetationAssetsArray' && $againAllowed) {
                    $v = 0;
                    foreach ($object->CONTENTOBJECTS as $index => $co) {
                        if (is_object($co) && trim($co->NAME) == 'Vector') {
                            $v++;
                            // found a new fallen tree
                            $minDistanceToSomething = 80000000;
                            foreach ($this->goldenBucket['Splines'] as $spline) {
                                foreach ($spline['Segments'] as $segment) {
                                    if ($segment['LocationCenter']['X'] < $co->content[0] - 2000) {
                                        continue;
                                    }
                                    if ($segment['LocationCenter']['X'] > $co->content[0] + 2000) {
                                        continue;
                                    }
                                    if ($segment['LocationCenter']['Y'] < $co->content[1] - 2000) {
                                        continue;
                                    }
                                    if ($segment['LocationCenter']['Y'] > $co->content[1] + 2000) {
                                        continue;
                                    }
                                    $minDistanceToSomething = min($minDistanceToSomething, $this->distance($co->content, $segment['LocationCenter']));
                                }
                            }
                            if ($minDistanceToSomething > 20000) {
                                $toRemove[] = $index;
                            }
                            //echo round($minDistanceToSomething)." ";
                        }
                    }
                    foreach ($toRemove as $tri) {
                        unset($object->CONTENTOBJECTS[$tri]);

                    }
                    $object->CONTENTOBJECTS[5]->value = (sizeof($object->CONTENTOBJECTS) - 6);
                    echo "NEW VALUE = ".(sizeof($object->CONTENTOBJECTS) - 6);
                }

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
            $db = unserialize(file_get_contents('db.db'));
            if (getUserIpAddr() != $db[$this->NEWUPLOADEDFILE][5]) {
                die("This does not seem to be your save file.");
            }
            echo "SAVING FILE " . $this->NEWUPLOADEDFILE . '.modified' . "<br>\n";
            file_put_contents('saves/' . $this->NEWUPLOADEDFILE . '.modified', $output);
            echo '<A href="saves/' . $this->NEWUPLOADEDFILE . '.modified' . '">Download your modified save here </A><br>';
            echo 'Want to upload this map again?<A href="upload.php">Add your renumbered save again</A><br>';
        } else {
            if ($againAllowed) {
                //echo "RESAVING FILE TO DISK - EMPTY NUMBERS BECAME A DOT " . $this->NEWUPLOADEDFILE . "<br>\n";
                file_put_contents('uploads/' . $this->NEWUPLOADEDFILE, $output);
                return 'AGAIN';
            }
        }

    }
}