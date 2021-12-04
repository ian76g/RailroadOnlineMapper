<?php
require_once 'utils/ArithmeticHelper.php';

/**
 * Class GVASParser
 */
class GVASParser
{
    private array $saveObject = array();
    private int $initialTreesDown = 1750;

    public array $goldenBucket = array();
    public string $owner;

    /**
     * @param string $x
     * @param bool $edit
     * @return false|string
     */
    public function parseData(string $x, bool $edit = false, $slotExtension = '')
    {
        $this->goldenBucket = array();
        $position = 0;

        $myHeader = new dtHeader();
        $position = $myHeader->unserialize($x, $position);
        $headerTotal = substr($x, 0, $position);
        $myHeader->content = $headerTotal;

        $this->saveObject['objects'][] = $myHeader;

        $myString = new dtString();
        $results = $myString->unserialize($x, $position);
//        $string = $results[0];
        $position = $results[1];
        $this->saveObject['objects'][] = $myString;

        while ($position < strlen($x)) {
            $myProperty = new dtProperty();
            $results = $myProperty->unserialize($x, $position);
            if ($results['0'] != 'EOF') {

                $original = substr($x, $position, $results[1] - $position);
                $test = $myProperty->serialize();
                if ($original != $test) {
                    file_put_contents('tmp_' . trim($myProperty->getName()), $original);
                    file_put_contents('tmp_' . trim($myProperty->getName()) . '.test', $test);
                }

                $this->saveObject['objects'][] = $myProperty;
                $position = $results[1];
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

        $this->owner = preg_replace('/[[:^alnum:]]/', "", $this->goldenBucket['Players'][0]['Name']) . $slotExtension;

        $carts = array('flatcar_logs', 'flatcar_cordwood', 'flatcar_stakes', 'flatcar_hopper', 'boxcar', 'flatcar_tanker');

        if (isset($this->goldenBucket['Frames'])) {
            foreach ($this->goldenBucket['Frames'] as $i => $frame) {
                if (false && in_array($frame['Type'], $carts)) {

                } else {
                    if (isset($this->goldenBucket['Boilers'][$i]))
                        $this->goldenBucket['Frames'][$i]['Boiler'] = $this->goldenBucket['Boilers'][$i];
                    if (isset($this->goldenBucket['Headlights'][$i]))
                        $this->goldenBucket['Frames'][$i]['Headlights'] = $this->goldenBucket['Headlights'][$i];
                    if (isset($this->goldenBucket['Compressors'][$i]))
                        $this->goldenBucket['Frames'][$i]['Compressor'] = $this->goldenBucket['Compressors'][$i];
                    if (isset($this->goldenBucket['Regulatorvalue'][$i]))
                        $this->goldenBucket['Frames'][$i]['Regulator'] = $this->goldenBucket['Regulatorvalue'][$i];
                    if (isset($this->goldenBucket['Smokestacktype'][$i]))
                        $this->goldenBucket['Frames'][$i]['Smokestack'] = $this->goldenBucket['Smokestacktype'][$i];
                    if (isset($this->goldenBucket['Generatorvalvevalue'][$i]))
                        $this->goldenBucket['Frames'][$i]['Generatorvalvevalue'] = $this->goldenBucket['Generatorvalvevalue'][$i];
                    if (isset($this->goldenBucket['Reverservalue'][$i]))
                        $this->goldenBucket['Frames'][$i]['Reverser'] = $this->goldenBucket['Reverservalue'][$i];
                    if (isset($this->goldenBucket['Tenders'][$i]))
                        $this->goldenBucket['Frames'][$i]['Tender'] = $this->goldenBucket['Tenders'][$i];
                    if (isset($this->goldenBucket['Marker'][$i]))
                        $this->goldenBucket['Frames'][$i]['Marker']['Front']['Right'] = $this->goldenBucket['Marker']['Lights']['Front']['Right'][$i];
                    if (isset($this->goldenBucket['Marker'][$i]))
                        $this->goldenBucket['Frames'][$i]['Marker']['Front']['Left'] = $this->goldenBucket['Marker']['Lights']['Front']['Left'][$i];
                    if (isset($this->goldenBucket['Marker'][$i]))
                        $this->goldenBucket['Frames'][$i]['Marker']['Rear']['Right'] = $this->goldenBucket['Marker']['Lights']['Rear']['Right'][$i];
                    if (isset($this->goldenBucket['Marker'][$i]))
                        $this->goldenBucket['Frames'][$i]['Marker']['Rear']['Left'] = $this->goldenBucket['Marker']['Lights']['Rear']['Left'][$i];
                }

                if (isset($this->goldenBucket['Freights'][$i]))
                    $this->goldenBucket['Frames'][$i]['Freight'] = $this->goldenBucket['Freights'][$i];

                if (isset($this->goldenBucket['Couplers'][$i]))
                    $this->goldenBucket['Frames'][$i]['Coupler'] = $this->goldenBucket['Couplers'][$i];

                if (isset($this->goldenBucket['Brakevalue'][$i]))
                    $this->goldenBucket['Frames'][$i]['Brake'] = $this->goldenBucket['Brakevalue'][$i];

            }
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


        if (isset($this->goldenBucket['Spline'])) {

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
                            'X' => round($startLocs[0]),
                            'Y' => round($startLocs[1]),
                            'Z' => round($startLocs[2])
                        ),
                        'LocationEnd' => array(
                            'X' => round($endLocs[0]),
                            'Y' => round($endLocs[1]),
                            'Z' => round($endLocs[2])
                        ),
                        'LocationCenter' => array(
                            'X' => round($startLocs[0] + ($endLocs[0] - $startLocs[0]) / 2),
                            'Y' => round($startLocs[1] + ($endLocs[1] - $startLocs[1]) / 2),
                            'Z' => round($startLocs[2] + ($endLocs[2] - $startLocs[2]) / 2)
                        ),
                        'Visible' => $this->goldenBucket['Spline']['Segments']['Visibility'][sizeof($segmentArray)],

                    );
                    $startPos++;

                    $cX = floor((200000 + $segmentArray[sizeof($segmentArray) - 1]['LocationCenter']['X']) / 100000);
                    $cY = floor((200000 + $segmentArray[sizeof($segmentArray) - 1]['LocationCenter']['Y']) / 100000);
                    if (!isset($segmentArray[sizeof($segmentArray) - 1]['CX'])) {
                        $segmentArray[sizeof($segmentArray) - 1]['CX'][] = $cX;
                    } else {
                        $segmentArray[sizeof($segmentArray) - 1]['CX'] = array_unique(array_merge(array($cX), $segmentArray[sizeof($segmentArray) - 1]['CX']));
                    }
                    if (!isset($segmentArray[sizeof($segmentArray) - 1]['CY'])) {
                        $segmentArray[sizeof($segmentArray) - 1]['CY'][] = $cY;
                    } else {
                        $segmentArray[sizeof($segmentArray) - 1]['CY'] = array_unique(array_merge(array($cY), $segmentArray[sizeof($segmentArray) - 1]['CY']));
                    }
                    $sX = floor((200000 + $segmentArray[sizeof($segmentArray) - 1]['LocationCenter']['X']) / 100000);
                    $sY = floor((200000 + $segmentArray[sizeof($segmentArray) - 1]['LocationCenter']['Y']) / 100000);
                    if ($sX != $cX) {
                        if (!isset($segmentArray[sizeof($segmentArray) - 1]['CX'])) {
                            $segmentArray[sizeof($segmentArray) - 1]['CX'][] = $sX;
                        } else {
                            $segmentArray[sizeof($segmentArray) - 1]['CX'] = array_unique(array_merge(array($sX), $segmentArray[sizeof($segmentArray) - 1]['CX']));
                        }
                    }
                    if ($sY != $cY) {
                        if (!isset($segmentArray[sizeof($segmentArray) - 1]['CY'])) {
                            $segmentArray[sizeof($segmentArray) - 1]['CY'][] = $sY;
                        } else {
                            $segmentArray[sizeof($segmentArray) - 1]['CY'] = array_unique(array_merge(array($sY), $segmentArray[sizeof($segmentArray) - 1]['CY']));
                        }
                    }

                    $eX = floor((200000 + $segmentArray[sizeof($segmentArray) - 1]['LocationCenter']['X']) / 100000);
                    $eY = floor((200000 + $segmentArray[sizeof($segmentArray) - 1]['LocationCenter']['Y']) / 100000);
                    if ($eX != $cX) {
                        if (!isset($segmentArray[sizeof($segmentArray) - 1]['CX'])) {
                            $segmentArray[sizeof($segmentArray) - 1]['CX'][] = $eX;
                        } else {
                            $segmentArray[sizeof($segmentArray) - 1]['CX'] = array_unique(array_merge(array($eX), $segmentArray[sizeof($segmentArray) - 1]['CX']));
                        }
                    }
                    if ($eY != $cY) {
                        if (!isset($segmentArray[sizeof($segmentArray) - 1]['CY'])) {
                            $segmentArray[sizeof($segmentArray) - 1]['CY'][] = $eY;
                        } else {
                            $segmentArray[sizeof($segmentArray) - 1]['CY'] = array_unique(array_merge(array($eY), $segmentArray[sizeof($segmentArray) - 1]['CY']));
                        }
                    }
                }

                $spline['Segments'] = $segmentArray;
                $this->goldenBucket['Splines'][] = $spline;
            }

//            $this->buildGraph();


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
//        unset($this->goldenBucket['Spline']);
        unset($this->goldenBucket['Save']);
        unset($this->goldenBucket['Industry']);

        /**
         * HANDLE DATA MANIPULATION AND SAVE FILE
         */


        $this->goldenBucket = $this->convert_from_latin1_to_utf8_recursively($this->goldenBucket);

        $json = json_encode($this->goldenBucket, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $json_min = json_encode($this->reduce_bucket(), JSON_UNESCAPED_UNICODE);

        file_put_contents('xx.json', $json);
        file_put_contents('xx.min.json', $json_min);

        if ($edit) {
            $tmp = $this->handleEditAndSave();
            return $tmp;
        }


        return $json_min;
    }


    /**
     * Encode array from latin1 to utf8 recursively
     * @param $dat
     * @return array|string
     */
    public function convert_from_latin1_to_utf8_recursively($dat)
    {
        if (is_string($dat)) {
            return utf8_encode($dat);
        } elseif (is_array($dat)) {
            $ret = [];
            foreach ($dat as $i => $d) $ret[$i] = self::convert_from_latin1_to_utf8_recursively($d);

            return $ret;
        } elseif (is_object($dat)) {
            foreach ($dat as $i => $d) $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);

            return $dat;
        } else {
            return $dat;
        }
    }


    /**
     * @param $a
     * @param $b
     * @return float
     */
    function distance($a, $b): float
    {

        return sqrt(pow($a[0] - $b['X'], 2) + pow($a[1] - $b['Y'], 2));
    }

    /**
     * @return string
     */
    function handleEditAndSave(): string
    {
        $sevenHundred = 700;
        if (isset($_POST['replant'])) {
            $sevenHundred = $_POST['replant'];
        }
        $output = '';
        $ah = new ArithmeticHelper();
        $ah->industries = $this->goldenBucket['Industries'];

        if (isset($_POST['from']) && isset($_POST['to'])) {
            $one = explode('-', $_POST['from']);
            $two = explode('-', $_POST['to']);
            $segment1 = $this->goldenBucket['Splines'][$one[0]]['Segments'][$one[1]];
            $segment2 = $this->goldenBucket['Splines'][$two[0]]['Segments'][$two[1]];
            try {
                $curvePoints = $ah->getCurveCoordsBetweenSegments($segment1, $segment2, $_POST['sinkBed']);
//                array_shift($curvePoints);
            } catch (Exception $e) {
                $i = 9;
            }
        }

        foreach ($this->saveObject['objects'] as $saveObjectIndex => $object) {
            if (is_object($object)) {
                if (trim($object->getName()) == 'RemovedVegetationAssetsArray' && isset($_POST['replant'])) {
                    $v = 0;
                    $toRemove = array();
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $vector) {
                        $v++;
                        // found a new fallen tree
//                        if ($v < $this->initialTreesDown) continue;
                        $treeX = floor((200000 + $vector->content[0]) / 100000);
                        $treeY = floor((200000 + $vector->content[1]) / 100000);

                        $minDistanceToSomething = 80000000;
//                      if (isset($this->goldenBucket['Segments'][$treeX][$treeY])) {
//                      foreach ($this->goldenBucket['Segments'][$treeX][$treeY] as $segment) {
                        foreach ($this->goldenBucket['Splines'] as $spline) {
                            if (!in_array($spline['Type'], array(0, 4))) continue;
                            foreach ($spline['Segments'] as $segment) {
                                if (!in_array($treeX, $segment['CX'])) continue;
                                if (!in_array($treeY, $segment['CY'])) continue;


                                if ($segment['LocationCenter']['X'] < $vector->content[0] - 6000) {
                                    continue;
                                }
                                if ($segment['LocationCenter']['X'] > $vector->content[0] + 6000) {
                                    continue;
                                }
                                if ($segment['LocationCenter']['Y'] < $vector->content[1] - 6000) {
                                    continue;
                                }
                                if ($segment['LocationCenter']['Y'] > $vector->content[1] + 6000) {
                                    continue;
                                }
                                $minDistanceToSomething = min($minDistanceToSomething, $this->distance($vector->content, $segment['LocationCenter']));
                            }
                        }
                        if ($minDistanceToSomething > $sevenHundred) {
                            if($ah->nearestIndustryDistance(array($vector->content[0], $vector->content[1])) < 9000) {
                                // too close to industry
                            } else {
                                $toRemove[] = $index;
                            }
                        }
                        //echo round($minDistanceToSomething)." ";
                    }
                    foreach ($toRemove as $tri) {
                        if (isset($_POST['replant'])) {
                            unset($object->CONTENTOBJECTS[3]->contentElements[$tri]);
                        } else {
                            $this->goldenBucket['Removed']['Vegetation'][$tri]['replant'] = true;
                        }

                    }
                    $object->CONTENTOBJECTS[3]->COUNTER->setValue((sizeof($object->CONTENTOBJECTS[3]->contentElements)));
//                    echo "NEW VALUE = " . (sizeof($object->CONTENTOBJECTS[3]->contentElements));
                }

                if (trim($object->getName()) == 'FrameNumberArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['number_' . $index]) && trim($_POST['number_' . $index])) {
                            if (!isset($object->CONTENTOBJECTS[3]->contentElements[$index]->lines[0])) {
                                $string = new dtString();
                                $string->nullBytes = 1;
                                $object->CONTENTOBJECTS[3]->contentElements[$index]->addLine($string);
                            }
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->lines[0]->string = trim($_POST['number_' . $index]);
                            // terminator = 2
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->terminator = pack('C', 2);
                            // second 4 = 1
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->secondFour = pack('i', 1);
                        }
                    }
                }
                $countryObj = null;
                if (trim($object->getName()) == 'FrameNameArray') {
                    if (isset($_POST['nameAllCountries'])) {
                        $countryObj22 = new CountryNames($_POST['nameAllCountries'], 22);
                        $countryObj15 = new CountryNames($_POST['nameAllCountries'], 15);
                    }
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['name_' . $index]) && trim($_POST['name_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index] = new dtTextProperty($_POST['name_' . $index]);
                        }
                        if (isset($_POST['nameAllCountries'])) {

                            $locos = array('porter_040', 'porter_042', 'eureka', 'heisler', 'class70', 'cooke260', 'climax');
                            $tenders = array('eureka_tender', 'class70_tender', 'cooke260_tender');
                            $carts = array('flatcar_logs', 'flatcar_cordwood', 'flatcar_stakes', 'flatcar_hopper', 'boxcar', 'flatcar_tanker');
                            $currentType = $this->goldenBucket['Frames'][$index]['Type'];
                            if ($_POST['renameWhat'] == 'locos' || $_POST['renameWhat'] == 'everything') {
                                if (in_array($currentType, $locos)) {
                                    $object->CONTENTOBJECTS[3]->contentElements[$index] = new dtTextProperty($countryObj22->getName());
                                }
                            }
                            if ($_POST['renameWhat'] == 'carts' || $_POST['renameWhat'] == 'everything') {
                                if (in_array($currentType, $carts)) {
                                    $object->CONTENTOBJECTS[3]->contentElements[$index] = new dtTextProperty($countryObj15->getName());
                                }
                            }
                            if ($_POST['renameWhat'] == 'tenders' || $_POST['renameWhat'] == 'everything') {
                                if (in_array($currentType, $tenders)) {
                                    $object->CONTENTOBJECTS[3]->contentElements[$index] = new dtTextProperty($countryObj15->getName());
                                }
                            }
//                                        'handcar': [this.engineRadius, 'white'],
                        }
                    }
                }

                if (trim($object->getName()) == 'FrameRotationArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['underground_' . $index]) && trim($_POST['underground_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->content = array(0, 90, 0);
                        }
                    }
                }

                if (trim($object->getName()) == 'FrameLocationArray') {
                    $spawnPositions = array(
                        [720, -2503, 10160],
                        [720, -461, 10160],
                        [1260, -2503, 10160],
                        [1260, -461, 10160],
                        [1800, -2503, 10160],
                        [1800, -461, 10160],
                    );
                    $used = 0;
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['underground_' . $index]) && trim($_POST['underground_' . $index])) {
//spawnZOffset = {
//    "heisler": 10233.,
//    "cooke260": 10239.,
//    "class70": 10194.,
//    "eureka": 10194.
//}
//
//def nextAvailableSpawn(gvas, pos):
//    framelocs = gvas.data.find("FrameLocationArray").data
//    for i, spawnPos in enumerate(spawnPositions):
//        for frameloc in framelocs:
//            checks = []
//            checks.append(
//                frameloc[0] > spawnPos[0]-270 and
//                frameloc[0] < spawnPos[0]+270)
//            checks.append(
//                frameloc[1] > spawnPos[1]-1021 and
//                frameloc[1] < spawnPos[1]+1021
//            )
//            if all(checks):
//                break
//        if all(checks):
//            continue
//        else:
//            return i
//    return None
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->content = $spawnPositions[max($used++, 5)];
                        }
                    }
                }

                if (trim($object->getName()) == 'FreightAmountArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $dtDynamic) {
                        if (isset($_POST['freightamount_' . $index]) && trim($_POST['freightamount_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['freightamount_' . $index]));
                        }
                    }
                }
                if (trim($object->getName()) == 'FreightTypeArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $dtDynamic) {
                        if (isset($_POST['cargoType_' . $index]) && trim($_POST['cargoType_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index] = new dtString($_POST['cargoType_' . $index]);
                        }
                    }
                }

                if (trim($object->getName()) == 'TenderFuelAmountArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['tenderamount_' . $index]) && trim($_POST['tenderamount_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(($_POST['tenderamount_' . $index]));
                        }
                    }
                }

                if (trim($object->getName()) == 'BrakeValueArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['allBrakes'])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(1);
                        }
                    }
                }

                //PlayerXPArray
                //PlayerMoneyArray
                //PlayerNameArray
                //PlayerLocationArray
                //PlayerRotationArray

                if (trim($object->getName()) == 'PlayerXPArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['xp_' . $index]) && trim($_POST['xp_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['xp_' . $index]));
                        }
                        if (isset($_POST['deletePlayer_' . $index])) {
                            unset($object->CONTENTOBJECTS[3]->contentElements[$index]);
//                            $object->CONTENTOBJECTS[3]->COUNTER->value--;
                        }
                    }
                }
                if (trim($object->getName()) == 'PlayerMoneyArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['money_' . $index]) && trim($_POST['money_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['money_' . $index]));
                        }
                        if (isset($_POST['deletePlayer_' . $index])) {
                            unset($object->CONTENTOBJECTS[3]->contentElements[$index]);
//                            $object->CONTENTOBJECTS[3]->COUNTER->value--;
                        }
                    }
                }
                if (trim($object->getName()) == 'PlayerNameArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['deletePlayer_' . $index])) {
                            unset($object->CONTENTOBJECTS[3]->contentElements[$index]);
//                                $object->CONTENTOBJECTS[3]->COUNTER->value--;
                        }
                    }
                }
                if (trim($object->getName()) == 'PlayerRotationArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['deletePlayer_' . $index])) {
                            unset($object->CONTENTOBJECTS[3]->contentElements[$index]);
//                            $object->CONTENTOBJECTS[3]->COUNTER->value--;
                        }
                    }
                }
                if (trim($object->getName()) == 'PlayerLocationArray') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['deletePlayer_' . $index])) {
                            unset($object->CONTENTOBJECTS[3]->contentElements[$index]);
//                            $object->CONTENTOBJECTS[3]->COUNTER->value--;
                        }
                    }
                }

                //IndustryStorageEduct1Array
                if (trim($object->getName()) == 'IndustryStorageEduct1Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['educt0_' . $index]) && trim($_POST['educt0_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['educt0_' . $index]));
                        }
                    }
                }
                //IndustryStorageEduct2Array
                if (trim($object->getName()) == 'IndustryStorageEduct2Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['educt1_' . $index]) && trim($_POST['educt1_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['educt1_' . $index]));
                        }
                    }
                }
                //IndustryStorageEduct3Array
                if (trim($object->getName()) == 'IndustryStorageEduct3Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['educt2_' . $index]) && trim($_POST['educt2_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['educt2_' . $index]));
                        }
                    }
                }
                //IndustryStorageEduct4Array
                if (trim($object->getName()) == 'IndustryStorageEduct4Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['educt3_' . $index]) && trim($_POST['educt3_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['educt3_' . $index]));
                        }
                    }
                }
                //IndustryStorageProduct1Array
                if (trim($object->getName()) == 'IndustryStorageProduct1Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['product0_' . $index]) && trim($_POST['product0_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['product0_' . $index]));
                        }
                    }
                }
                //IndustryStorageProduct2Array
                if (trim($object->getName()) == 'IndustryStorageProduct2Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['product1_' . $index]) && trim($_POST['product1_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['product1_' . $index]));
                        }
                    }
                }
                //IndustryStorageProduct3Array
                if (trim($object->getName()) == 'IndustryStorageProduct3Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['product2_' . $index]) && trim($_POST['product2_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['product2_' . $index]));
                        }
                    }
                }
                //IndustryStorageProduct4Array
                if (trim($object->getName()) == 'IndustryStorageProduct4Array') {
                    foreach ($object->CONTENTOBJECTS[3]->contentElements as $index => $textProp) {
                        if (isset($_POST['product3_' . $index]) && trim($_POST['product3_' . $index])) {
                            $object->CONTENTOBJECTS[3]->contentElements[$index]->setValue(trim($_POST['product3_' . $index]));
                        }
                    }
                }


                if (isset($curvePoints[0]) && sizeof($curvePoints[0])) {

                    if (trim($object->getName()) == 'SplineControlPointsArray') {

                        $controlPointStartTrackIndex = sizeof($object->CONTENTOBJECTS[3]->contentElements);
                        // add all curve points as Vector to the array
                        foreach ($curvePoints[0] as $point) {
                            $v = new dtVector($point);
                            $object->CONTENTOBJECTS[3]->addElement($v);
                        }
                        $controlPointEndTrackIndex = sizeof($object->CONTENTOBJECTS[3]->contentElements) - 1;
                        $controlPointStartBedIndex = sizeof($object->CONTENTOBJECTS[3]->contentElements);
                        if($_POST['curveBed']!= 'none'){
                            // add all bed curve points as Vector to the array
                            foreach ($curvePoints[1] as $point) {
                                $v = new dtVector($point);
                                $object->CONTENTOBJECTS[3]->addElement($v);
                            }
                            $controlPointEndBedIndex = sizeof($object->CONTENTOBJECTS[3]->contentElements) - 1;
                        }
                    }

                    if (trim($object->getName()) == 'SplineLocationArray') {
                        $beforeFuddelingSplines = sizeof($object->CONTENTOBJECTS[3]->contentElements);
                        $v = new dtVector($curvePoints[0][0]);
                        $object->CONTENTOBJECTS[3]->addElement($v);
                        if($_POST['curveBed']!= 'none') {
                            $v = new dtVector($curvePoints[0][1]);
                            $object->CONTENTOBJECTS[3]->addElement($v);
                            $afterFuddelingSplines = sizeof($object->CONTENTOBJECTS[3]->contentElements) - 1;
                        }
                    }

                    if (trim($object->getName()) == 'SplineTypeArray') {
                        $v = new dtDynamic('IntProperty');
                        $v->pack = 'V';
                        $v->setValue($_POST['curveTrack']);
                        $object->CONTENTOBJECTS[3]->addElement($v);
                        if($_POST['curveBed']!= 'none') {
                            $v = new dtDynamic('IntProperty');
                            $v->pack = 'V';
                            $v->setValue($_POST['curveBed']);
                            $object->CONTENTOBJECTS[3]->addElement($v);
                        }
                    }

                    if (trim($object->getName()) == 'SplineControlPointsIndexStartArray') {
                        $v = new dtDynamic('IntProperty');
                        $v->pack = 'V';
                        $v->setValue($controlPointStartTrackIndex);
                        $object->CONTENTOBJECTS[3]->addElement($v);
                        if($_POST['curveBed']!= 'none') {
                            $v = new dtDynamic('IntProperty');
                            $v->pack = 'V';
                            $v->setValue($controlPointStartBedIndex);
                            $object->CONTENTOBJECTS[3]->addElement($v);
                        }
                    }

                    if (trim($object->getName()) == 'SplineControlPointsIndexEndArray') {
                        $v = new dtDynamic('IntProperty');
                        $v->pack = 'V';
                        $v->setValue($controlPointEndTrackIndex);
                        $object->CONTENTOBJECTS[3]->addElement($v);
                        if($_POST['curveBed']!= 'none') {
                            $v = new dtDynamic('IntProperty');
                            $v->pack = 'V';
                            $v->setValue($controlPointEndBedIndex);
                            $object->CONTENTOBJECTS[3]->addElement($v);
                        }
                    }

                    if (trim($object->getName()) == 'SplineSegmentsVisibilityArray') {
                        // add all curve points as Vector to the array
                        for ($spex = 0; $spex < sizeof($curvePoints[0]); $spex++) {
                            $v = new dtDynamic('BoolProperty');
                            $v->pack = 'C';
                            $v->setValue(1);
                            if($spex == 0) {
                                $v->setValue(0);
                            }
                            $object->CONTENTOBJECTS[3]->addElement($v);
                        }
                        if($_POST['curveBed']!= 'none') {
                            for ($spex = 0; $spex <= sizeof($curvePoints[1]); $spex++) {
                                $v = new dtDynamic('BoolProperty');
                                $v->pack = 'C';
                                $v->setValue(1);
                                $object->CONTENTOBJECTS[3]->addElement($v);
                            }
                        }
                    }
                    if (trim($object->getName()) == 'SplineVisibilityStartArray') {
                        $v = new dtDynamic('IntProperty');
                        $v->pack = 'V';
                        $v->setValue($controlPointStartTrackIndex+1);
                        $object->CONTENTOBJECTS[3]->addElement($v);
                        if($_POST['curveBed']!= 'none') {
                            $v = new dtDynamic('IntProperty');
                            $v->pack = 'V';
                            $v->setValue($controlPointStartBedIndex);
                            $object->CONTENTOBJECTS[3]->addElement($v);
                        }
                    }
                    if (trim($object->getName()) == 'SplineVisibilityEndArray') {
                        $v = new dtDynamic('IntProperty');
                        $v->pack = 'V';
                        $v->setValue($controlPointEndTrackIndex);
                        $object->CONTENTOBJECTS[3]->addElement($v);
                        if($_POST['curveBed']!= 'none') {
                            $v = new dtDynamic('IntProperty');
                            $v->pack = 'V';
                            $v->setValue($controlPointEndBedIndex);
                            $object->CONTENTOBJECTS[3]->addElement($v);
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
            if (getUserIpAddr()!='local' && (!isset($db[$this->owner][5]) || getUserIpAddr() != $db[$this->owner][5])) {
                $secondParts = explode('.', $db[$this->owner][5]);
                echo 'Your IP is: ' . getUserIpAddr() . ' but game was uploaded from: ' . $secondParts[0] . '.*.*.' . $secondParts[3] . " [" . $this->owner . "]\n";
                die("This does not seem to be your save file.");
            }
        }
        return $output;
    }

    private function buildGraph()
    {
        global $industryTracks;
        $ah = new ArithmeticHelper();
        $ah->industries = $this->goldenBucket['Industries'];

        $industryTracks = array();

        $segments = array();
        foreach ($this->goldenBucket['Splines'] as $spIndex => $spline) {
            if (!in_array($spline['Type'], array(0, 4))) {
                continue;
            }
            foreach ($spline['Segments'] as $seIndex => $segment) {
                if ($segment['Visible'] != 1) {
                    continue;
                }

                $id = $spIndex . '-' . $seIndex;

                $divisionS = floor($segment['LocationStart']['X'] / 10000) . '-' . floor($segment['LocationStart']['Y'] / 10000);
                $divisionE = floor($segment['LocationEnd']['X'] / 10000) . '-' . floor($segment['LocationEnd']['Y'] / 10000);

                $newNode = new Node($id, array($segment['LocationStart'], $segment['LocationEnd']), $ah);

                $segments[$divisionS][$id] = $newNode;
                $segments[$divisionE][$id] = $newNode;

                foreach ($this->goldenBucket['Industries'] as $i => $industry) {
                    $d = $ah->dist($industry['Location'], $segment['LocationCenter']);
                    if (!isset($industryTracks[$i]) || $industryTracks[$i]['d'] > $d) {
                        $industryTracks[$i]['d'] = $d;
                        $industryTracks[$i]['trackNode'] = $segments[$divisionS][$id];
                    }
                }

            }
        }

        foreach ($this->goldenBucket['Switchs'] as $swIndex => $switch) {
            $ses = $this->findSwitchEndpoints($switch);
            $divisionS = floor($ses[0]['X'] / 10000) . '-' . floor($ses[0]['Y'] / 10000);
            $divisionSS = floor($ses[1]['X'] / 10000) . '-' . floor($ses[1]['Y'] / 10000);
            $divisionSB = floor($ses[2]['X'] / 10000) . '-' . floor($ses[2]['Y'] / 10000);

            $switchNode = new SwitchNode('SW-' . $swIndex, $ses, $switch['Side'], $ah);

            $segments[$divisionS]['SW-' . $swIndex] = $switchNode;
            $segments[$divisionSS]['SW-' . $swIndex] = $switchNode;
            $segments[$divisionSB]['SW-' . $swIndex] = $switchNode;
        }

        foreach ($segments as $region => $regionSegments) {
//            echo "$region..";
            foreach ($regionSegments as $index => $node) {
                foreach ($segments as $pRegion => $pRegionSegments) {
                    if ($region != $pRegion) {
                        continue;
                    }
                    //                    echo "$pRegion\n";
                    foreach ($pRegionSegments as $pindex => $partner) {
                        if ($pindex == $index) {
                            continue; // same node
                        }
                        /** @var Node $node */
                        if ($node->hasEndpointLike($partner)) {
                            $node->addPartner($partner);
                            $partner->addPartner($node);
                        }
                    }
                }
            }
        }

        foreach ($industryTracks as $i => $industryTrack) {
            $trackNode = $industryTrack['trackNode'];
            /** @var Node $trackNode */
            echo $trackNode->near . " :";
            $this->driveAlongTrack($i, $trackNode);


            echo "\n##############################################\n";
        }


        echo "1";
//        ksort($segments);
//        print_r($segments);
//        die();

    }

    function driveAlongTrack($iI, &$node, $passed = array())
    {
        global $industryTracks;
        if (sizeof($passed) > 500) return;
        if (isset($node->ww[$iI][0]) && $node->ww[$iI][0] < sizeof($passed)) {
            return;
        }
        $node->ww[$iI] = sizeof($passed);
        $passed[] = $node->id;
//echo $node->id." ";
        if (sizeof($node->nextNodes) < 2) {
//            echo $node->id.' ';
//echo 'DEAD END'."\n"; sleep(1);
            return;
        }

        if (sizeof($node->nextNodes) == 3) {
//            echo "SWITCH $node->id\n";
            foreach ($node->nextNodes as $nnI => $nextNode) {
                /** @var Node $nextNode */

                if (!in_array($nextNode->id, $passed)) {
                    $this->driveAlongTrack($iI, $nextNode, $passed);
                }
            }
//            echo "\n BACK at SWITCH  $node->id ";
        }
        foreach ($node->nextNodes as $nnI => $nextNode) {
            // check if i reached an industry
            foreach ($industryTracks as $industryIndex => $industryTrack) {
                if ($industryTrack['trackNode']->id == $nextNode->id) {
//                    echo ' REACHED '.$industryIndex."\n";
                    return;
                }
            }
            /** @var Node $nextNode */
            if (!in_array($nextNode->id, $passed)) {
                $this->driveAlongTrack($iI, $nextNode, $passed);
            } else {
                // came from nnI
                $node->ww[$iI] = array(sizeof($passed), $nnI);
            }
        }

        return;
    }


    function findSwitchEndpoints($switch): array
    {

        /**
         * 0 = SwitchLeft           = lever left switch going left
         * 1 = SwitchRight          = lever right switch going right
         * 2 =                      = Y
         * 3 =                      = Y mirror
         * 4 = SwitchRightMirror    = lever left switch going right
         * 5 = SwitchLeftMirror     = lever right switch going left
         * 6 = SwitchCross90        = cross
         */
        switch ($switch['Type']) {
            case 2:
            case 5:
            case 0 :
                $dir = -5.7;
                break;
            case 1 :
            case 3 :
            case 4:
                $dir = 5.7;
                break;
            default:
                $dir = 0;
        }


        $length = 1880;
        $x = round($switch['Location'][0] + cos(deg2rad($switch['Rotation'][1] + 90)) * $length);
        $y = round($switch['Location'][1] + sin(deg2rad($switch['Rotation'][1] + 90)) * $length);
        $point = array('X' => $switch['Location'][0], 'Y' => $switch['Location'][1], 'Z' => $switch['Location'][2]);
        $straight = array('X' => $x, 'Y' => $y, 'Z' => $switch['Location'][2]);

        $x = round($switch['Location'][0] + cos(deg2rad($switch['Rotation'][1] + 90 + $dir)) * ($length - 7));
        $y = round($switch['Location'][1] + sin(deg2rad($switch['Rotation'][1] + 90 + $dir)) * ($length - 7));
        $bend = array('X' => $x, 'Y' => $y, 'Z' => $switch['Location'][2]);

        return array($point, $straight, $bend);

    }

    private function reduce_bucket(): array
    {
        $reduced = $this->goldenBucket;
        $carts = array('flatcar_logs', 'flatcar_cordwood', 'flatcar_stakes', 'flatcar_hopper', 'boxcar', 'flatcar_tanker');

        // We don't use player rotation
        foreach ($reduced['Players'] as $index => $player) {
            unset($reduced['Players'][$index]['Rotation']);
        }

        if(isset($reduced['Frames'])) {
            // Carts don't need the same attributes as locomotives
            foreach ($reduced['Frames'] as $index => $frame) {
                if (in_array($frame['Type'], $carts)) {
                    unset($reduced['Frames'][$index]['Boiler']);
                    unset($reduced['Frames'][$index]['Headlights']);
                    unset($reduced['Frames'][$index]['Compressor']);
                    unset($reduced['Frames'][$index]['Regulator']);
                    unset($reduced['Frames'][$index]['Smokestack']);
                    unset($reduced['Frames'][$index]['Generatorvalvevalue']);
                    unset($reduced['Frames'][$index]['Reverser']);
                    unset($reduced['Frames'][$index]['Tender']);
                    unset($reduced['Frames'][$index]['Marker']);
                } else {
                    unset($reduced['Frames'][$index]['Freight']);
                }
            }
        }
        // We don't show Watertower level
        if (isset($reduced['Watertowers'])) {
            foreach ($reduced['Watertowers'] as $index => $watertower) {
                unset($reduced['Watertowers'][$index]['Waterlevel']);
            }
        }

        // Industries 1-9 have a fixed rotation
        foreach ($reduced['Industries'] as $index => $watertower) {
            if (in_array($reduced['Industries'][$index]['Type'], range(1, 9))) {
                unset($reduced['Industries'][$index]['Rotation']);
            }
        }

        // Remove the Z-axis
        foreach ($reduced['Removed']['Vegetation'] as $index => $tree) {
            unset($reduced['Removed']['Vegetation'][$index][2]);
        }
        foreach ($reduced['Players'] as $index => $player) {
            unset($reduced['Players'][$index]['Location'][2]);
        }
        if(isset($reduced['Frames'])) {
            foreach ($reduced['Frames'] as $index => $frame) {
                unset($reduced['Frames'][$index]['Rotation'][2]);
//            unset($reduced['Frames'][$index]['Location'][2]);
            }
        }
        if (isset($reduced['Watertowers'])) {
            foreach ($reduced['Watertowers'] as $index => $watertower) {
                unset($reduced['Watertowers'][$index]['Location'][2]);
                unset($reduced['Watertowers'][$index]['Rotation'][2]);
            }
        }
        if (isset($reduced['Switchs'])) {
            foreach ($reduced['Switchs'] as $index => $switch) {
                unset($reduced['Switchs'][$index]['Rotation'][2]);
                unset($reduced['Switchs'][$index]['Location'][2]);
            }
        }
        foreach ($reduced['Industries'] as $index => $industry) {
            unset($reduced['Industries'][$index]['Rotation'][2]);
            unset($reduced['Industries'][$index]['Location'][2]);
        }

        // Remove invisible segments
//        foreach ($reduced['Splines'] as $index => $spline) {
//            $spline_segments = array();
//            foreach ($spline['Segments'] as $sindex => $segment) {
//                if ($reduced['Splines'][$index]['Segments'][$sindex]['Visible'] === 1) {
//                    $spline_segments[] = $reduced['Splines'][$index]['Segments'][$sindex];
//                }
//            }
//            $reduced['Splines'][$index]['Segments'] = $spline_segments;
//        }

        return $reduced;
    }
}

class SwitchNode extends Node
{
    public function __construct($id, $endpoints, $side, ArithmeticHelper $ah)
    {
        parent::__construct($id, $endpoints, $ah);
        $this->side = $side;
    }
}

class Node
{
    var array $endpoints = array();
    var array $nextNodes = array();
    var int $id;
    var ArithmeticHelper $ah;
    var string $near;

    public function __construct($id, $endpoints, ArithmeticHelper $ah)
    {
        $this->ah = $ah;
        $this->id = $id;
        foreach ($endpoints as $endpoint) {
            $this->endpoints[] = $endpoint;
        }
        $this->near = $this->ah->nearestIndustry($endpoint);
        // DEBUG
        unset($this->ah);
    }

    /**
     * @param Node $node
     */
    public function addPartner(Node $node)
    {
        $this->nextNodes[$node->id] = &$node;
    }

    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * @param Node $node
     * @return bool
     */
    public function hasEndPointLike(Node $node): bool
    {
        foreach ($node->getEndpoints() as $point) {
            foreach ($this->endpoints as $ownPoint) {
                if (
                    abs($ownPoint['X'] - $point['X']) < 20 &&
                    abs($ownPoint['Y'] - $point['Y']) < 20 &&
                    abs($ownPoint['Z'] - $point['Z']) < 20
                ) {
                    return true;
                }
            }
        }
        return false;
    }

}

class CountryNames
{
    var $names;
    var $maxLength = 25;

    public function getName()
    {
        while (true) {
            if (!sizeof($this->NAMEs)) {
                return "END OF FILE";
            }
            $name = array_shift($this->NAMEs);
            if (!trim($name)) {
                continue;
            }
            if (strlen($name) < $this->maxLength) {
                return $name;
            }
        }
    }

    public function __construct($type, $maxLength)
    {
        $this->maxLength=$maxLength;
        if (file_exists('includes/' . $type . '.txt')) {
            $data = file_get_contents('includes/' . $type . '.txt');
            $data = explode("\n", $data);
            array_shift($data); // header
            $this->NAMEs = $data;
        } else {
            $this->NAMEs = array('ERROR');
        }
        shuffle($this->NAMEs);
    }
}