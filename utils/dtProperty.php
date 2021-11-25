<?php

/**
 * Class dtProperty
 */
class dtProperty extends dtAbstractData
{
    var string $x;
    var int $position;

    var array $CONTENTOBJECTS;
    private string $NAME;
    var string $TYPE;
    var $RESULTROWS;
    var string $ITEMTYPE = '';
    var string $SUBTYPE = '';
    var string $GUID = '';

    var string $content;


    public function __construct()
    {

    }

    function unserialize($fromX, $position)
    {
        $this->x = $fromX;
        $this->position = $position;

        $resultRows = $this->readUEProperty();
        $this->RESULTROWS = $resultRows;
        unset($this->x);
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
//   echo "($type) ";
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
                $arrayObject = new dtArray();
                $elem = array();
                $myString = new dtString();
                $results = $myString->unserialize($this->x, $this->position);
                $itemType = trim($results[0]);
                $this->ITEMTYPE = $itemType;
                $this->position = $results[1];
                $arrayObject->setItemType($myString);
//                echo "(ItemType is ".trim($myString->string).")";
//                $this->CONTENTOBJECTS[] = $myString;

                $arrayObject->setByte(substr($this->x, $this->position, 1));
                $this->position++;

                $arrayCounter = new dtDynamic('Arraycounter');
                $sub = substr($this->x, $this->position, 4);
                $arrayCounter->setValue(unpack('I', substr($this->x, $this->position, 4))[1]);
                $arrayCounter->pack = 'I';
//                $this->CONTENTOBJECTS[] = $arrayCounter;
//            echo "(ARRAY-COUNT = ".$arrayCounter->value.") ";
                $this->position += 4;
                $arrayObject->setCounter($arrayCounter);

                $toGo = $arrayCounter->getValue();
                switch ($itemType) {
                    case 'StrProperty':
                        for ($i = 0; $i < $toGo; $i++) {
                            $myString = new dtString();
                            $myString->skipNullByteStrings = false;
                            $results = $myString->unserialize($this->x, $this->position);
                            $str = trim($results[0]);
                            $this->position = $results[1];
                            $myString->ARRCOUNTER = $i;
                            $arrayObject->addElement($myString);
//                            $this->CONTENTOBJECTS[] = $myString;
                            //@$goldenBucket[$name][]= $str;
                            $elem[] = array($pieces, $str);
                        }
                        $this->CONTENTOBJECTS[] = $arrayObject;
                        return $elem;

                    case 'StructProperty' :
                        $structObject = new dtStruct();

                        $myString = new dtString();
                        $results = $myString->unserialize($this->x, $this->position);
                        $name = trim($results[0]);
                        $this->position = $results[1];
                        //$this->CONTENTOBJECTS[] = $myString;
                        $structObject->setName($myString);

                        $myString = new dtString();
                        $results = $myString->unserialize($this->x, $this->position);
                        $type = trim($results[0]);
                        $this->position = $results[1];
                        //$this->CONTENTOBJECTS[] = $myString;
                        $structObject->setType($myString);

                        $lenght = unpack('P', substr($this->x, $this->position, 8))[1];
                        $this->position += 8;
                        //$this->CONTENTOBJECTS[] = pack('P', $lenght);
                        $structObject->setLength(array('P', $lenght));

//                    echo "[[$type][$lenght]]";
                        $myString = new dtString();
                        $results = $myString->unserialize($this->x, $this->position);
                        $subType = trim($results[0]);
                        $this->SUBTYPE = $subType;
                        $this->position = $results[1];
                        //$this->CONTENTOBJECTS[] = $myString;
                        $structObject->setSubType($myString);

                        //$this->CONTENTOBJECTS[] = substr($this->x, $this->position, 17);
                        $structObject->setSeventeenBytes(substr($this->x, $this->position, 17));
                        $this->position++;
                        $this->position += 16;

                        $arrayObject->setStructObject($structObject);

                        switch ($subType) {
                            case "Vector":
                            case "Rotator":
                                for ($i = 0; $i < $toGo; $i++) {
                                    $myVector = new dtVector();
                                    $myVector->ARRCOUNTER = $i;
                                    $tmp = $myVector->unserialize($this->x, $this->position);
                                    $this->position = $tmp[0];
                                    $elem[] = array($pieces, $tmp[1]);
                                    //$this->CONTENTOBJECTS[] = $myVector;
                                    $arrayObject->addElement($myVector);

                                }
                                $this->CONTENTOBJECTS[] = $arrayObject;
                                return $elem;

                            default:
                                echo "$name not implemented BOO\n";
                                die();
                        }

                    case 'FloatProperty':
                        for ($i = 0; $i < $toGo; $i++) {
                            $floatObject = new dtDynamic('FloatProperty');
                            $floatObject->pack = 'g';
                            $floatObject->setValue(unpack('g', substr($this->x, $this->position, 4))[1]);
                            $this->position += 4;
                            $floatObject->ARRCOUNTER = $i;
                            $arrayObject->addElement($floatObject);
//                            $this->CONTENTOBJECTS[] = $floatObject;
                            //@$goldenBucket[$name][]= $float;
                            $elem[] = array($pieces, $floatObject->getValue());
                        }
                        $this->CONTENTOBJECTS[] = $arrayObject;
                        return $elem;

                    case 'IntProperty':
                        for ($i = 0; $i < $toGo; $i++) {
                            $int = unpack('V', substr($this->x, $this->position, 4))[1];
                            $nD = new dtDynamic('IntProperty');
                            $nD->setValue($int);
                            $nD->ARRCOUNTER = $i;
                            $nD->pack = 'V';
//                            $this->CONTENTOBJECTS[] = $nD;
                            $arrayObject->addElement($nD);
                            $this->position += 4;
                            //@$goldenBucket[$name][]= $int;
                            $elem[] = array($pieces, $int);
                        }
                        $this->CONTENTOBJECTS[] = $arrayObject;
                        return $elem;

                    case 'BoolProperty':
                        for ($i = 0; $i < $toGo; $i++) {
                            $boolObject = new dtDynamic('BoolProperty');
                            $boolObject->pack='C';
                            $boolObject->setValue(unpack('C', substr($this->x, $this->position, 1))[1]);
//                            $this->CONTENTOBJECTS[] = substr($this->x, $this->position, 1);
                            $this->position += 1;
                            $arrayObject->addElement($boolObject);
                            //@$goldenBucket[$name][]= $int;
                            $elem[] = array($pieces, $boolObject->getValue());
                        }
                        $this->CONTENTOBJECTS[] = $arrayObject;
                        return $elem;

                    case 'TextProperty':
                        //echo "Textproperty incoming at " . $this->position . " for Name $name\n";
                        for ($i = 0; $i < $toGo; $i++) {
                            $createEmptyNumber = false;
                            $createEmptyName = false;
                            if (trim($this->NAME) == 'FrameNumberArray') {
//                                $createEmptyNumber = true;
                            }
                            if (trim($this->NAME) == 'FrameNameArray') {
//                                $createEmptyName = true;
                            }
                            $textPropertyObject = $this->readTextProperty($i, $createEmptyNumber, $createEmptyName)[0];
                            $textPropertyObject->x='';
//var_dump($textPropertyObject);
                            $arrayObject->addElement($textPropertyObject);
                            $str = '';
                            foreach($textPropertyObject->lines as $lineObject){
                                if(get_class($lineObject) == 'dtString'){
                                    if($lineObject->NAME == 'HUMAN_TEXT'){
                                        $str.=trim($lineObject->string).'<br>';
                                    }
                                } else {
                                    //dtTextProperty
                                    $str.=$lineObject->getText();
                                }
                            }
                            $elem[] = array($pieces, $str);
                            //echo "...-[$cartText]-..."
                        }
                        $this->CONTENTOBJECTS[] = $arrayObject;
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
     * @param $cartIndex
     * @param false $createEmptyNumber
     * @return array
     *
     *
     * There is another format (the strange one you sent me)
     * Basically when you finish reading an entry and start reading the next one,
     * you need to read the first int32 to know whether it’s formatted or not
     * Usually you get
     * 02 00 00 00 if there’s a regular text entry,
     * 00 00 00 00 if it’s a null text entry, and
     * 01 00 00 00 if it’s formatted
     * If you get 02 or 00, then read the separator ff and the « opt » which is 01 00 00 00
     * if there’s a UEString, and 00 00 00 00 if there’s not.
     * And then onto the next index of the array
     * However if you get 01 00 00 00 as first value, then it’s formatted,
     * the separator is 03, then int64 08 00 00 00 00 00 00 00 and empty byte 00
     * Then the format specifiers :
     * UEString (the magic string I don’t know what it does but is always the same),
     * UEString (formatted) int 32 with value
     * 02 00 00 00 (probably the number of field in the formatter)
     * and one last UEString with "0"
     * Then a special separator 04
     * And then the first line as a special text property:
     * 02 00 00 00
     * ff
     * 01 00 00 00
     * Then 2 UEString
     * The first one being the actual content of the first line,
     * the second one being the "1" we always see, but that can be discarded when reading and put back when writing
     * And that field ends with one byte 04
     * And then the second line, which will always start with 02 00 00 00
     * Then ff
     * Then if it’s empty 00 00 00 00, or else 01 00 00 00 then UEString
     * I don’t think it ends with 04 for that one (writing that from memory)
     * And that’s the full formatted TextProperty array index
     */
    function readTextProperty($cartIndex, $createEmptyNumber = false, $createEmptyName = false)
    {
        $textPropertyObject = new dtTextProperty();
        $terminator = unpack('C', substr($this->x, $this->position, 1))[1];
        $textPropertyObject->setTerminator(substr($this->x, $this->position, 1));
//        $this->CONTENTOBJECTS[] = ;
        $this->position++;
        $firstFour = unpack('i', substr($this->x, $this->position, 4))[1];
        $textPropertyObject->setFirstFour(substr($this->x, $this->position, 4));
        //$this->CONTENTOBJECTS[] = substr($this->x, $this->position, 4); //000000FF
        $this->position += 4;
        $secondFour = unpack('i', substr($this->x, $this->position, 4))[1];
        $textPropertyObject->setSecondFour(substr($this->x, $this->position, 4));
        //$this->CONTENTOBJECTS[] = substr($this->x, $this->position, 4); //00000000 in case of terminator 0
        $this->position += 4;

//        if ($terminator == 0 && $createEmptyNumber) {
//            // HERE WE HAVE TO IMPLEMENT A NEW CART NUMBER (eg. a dot)
//            // TERMINATOR must be 02
//            // firstFour have to be 000000FF
//            // secondFour have to be 01000000
//            // and then comes the text as length 2 bytes 02000000
//            // and the text itself .0x00
//            //
//            $this->CONTENTOBJECTS[sizeof($this->CONTENTOBJECTS) - 3] = pack('C', 2);
//            $this->CONTENTOBJECTS[sizeof($this->CONTENTOBJECTS) - 1] = pack('i', 1);
//            $newText = new dtString();
//            $newText->nullBytes = 1;
//            $newText->string = '.' . hex2bin('00');
//            $this->CONTENTOBJECTS[] = $newText;
//        }
//
//        if ($terminator == 0 && $createEmptyName) {
//            //* However if you get 01 00 00 00 as first value, then it’s formatted,
//            $this->CONTENTOBJECTS[sizeof($this->CONTENTOBJECTS) - 3] = pack('C', 1);
//            //* the separator is 03,
//            $this->CONTENTOBJECTS[sizeof($this->CONTENTOBJECTS) - 2] = hex2bin(str_replace(' ', '', '00 00 00 03'));
//            // then int64 08 00 00 00 00 00 00 00 and empty byte 00
//            $this->CONTENTOBJECTS[sizeof($this->CONTENTOBJECTS) - 1] =
//                hex2bin(str_replace(' ', '', '08 00 00 00 00 00 00 00 00'));
//            //* Then the format specifiers :
//            //* UEString (the magic string I don’t know what it does but is always the same),
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '', '21 00 00 00')); // length of formatter
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '',
//                '35 36 46 38 44 32 37 31 34 39 ' .
//                '43 43 35 45 32 44 31 32 31 30 ' .
//                '33 42 42 45 42 46 43 41 39 30 ' .
//                '39 37 00 '));
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '',
//                '0b 00 00 00 ' .
//                '7b 30 7d 3c 62 72 3e 7b 31 7d 00')  // {0} <br> {1}
//            ); // formatter
//            //* 02 00 00 00 (probably the number of field in the formatter)
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '', '02 00 00 00')); // 2 texts coming
//
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '', '02 00 00 00 30 00')); // text rowid with line number 0
//            //* Then a special separator 04
//            $this->CONTENTOBJECTS[] = hex2bin('04');
//            $this->CONTENTOBJECTS[] = hex2bin('02'); // terminator 2
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '', '00 00 00 ff 01 00 00 00')); // first 4 and second 4
//            // first Text
//            $newText = new dtString();
//            $newText->nullBytes = 1;
//            $newText->string = '.' . hex2bin('00');
//            $this->CONTENTOBJECTS[] = $newText;
//
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '', '02 00 00 00 31 00')); // text rowid with line number 1
//            //* Then a special separator 04
//            $this->CONTENTOBJECTS[] = hex2bin('04');
//            // second Text
//            $this->CONTENTOBJECTS[] = hex2bin(str_replace(' ', '', '02 00 00 00 ff 00 00 00 00'));
//        }


        switch ($terminator) {
            case 0 :
                $cartText = "";
                break;

            case 1:
                $textPropertyObject->setUnknown(substr($this->x, $this->position, 5));
                //$this->CONTENTOBJECTS[] = substr($this->x, $this->position, 5);  // UNKONWN 0000000308
                $this->position += 5;

                $myString = new dtString();
                $myString->NAME = 'Type of Next Thing';
                $results = $myString->unserialize($this->x, $this->position);
                $typeOfNextThing = $results[0];
                $this->position = $results[1];
                //$this->CONTENTOBJECTS[] = $myString;
                $textPropertyObject->setTypeOfNextThing($myString);

                $myString = new dtString();
                $myString->NAME = 'Formatter';
                $results = $myString->unserialize($this->x, $this->position);
                $stringFormatter = trim($results[0]);
                $this->position = $results[1];
                //$this->CONTENTOBJECTS[] = $myString;
                $textPropertyObject->setFormatter($myString);

                $textPropertyObject->setNumberOfLines(array('i', substr($this->x, $this->position, 4)));
                $this->position += 4;
                //$this->CONTENTOBJECTS[] = substr($this->x, $this->position, 4);

                for ($lineNumber = 0; $lineNumber < $textPropertyObject->numberOfLines; $lineNumber++) {
                    $myString = new dtString();
                    $myString->NAME='Line Number';
                    $results = $myString->unserialize($this->x, $this->position);
                    $this->position = $results[1];
                    //$this->CONTENTOBJECTS[] = $myString;  // string contains $lineNumber
                    $textPropertyObject->addLine($myString);

                    $test = unpack('C', substr($this->x, $this->position, 1))[1];
                    $textPropertyObject->addTest(substr($this->x, $this->position, 1));
                    //$this->CONTENTOBJECTS[] = substr($this->x, $this->position, 1);
                    $this->position++;
                    if ($test != 4) {
                        die('horribly');
                    } else {
                        $textPropertyObject->addLine($this->readTextProperty($cartIndex)[0]);
                        $textPropertyObject->addTest('dummy');
                    }
                }
                break;

            case 2:
                if ($secondFour == 0) {
                    $cartText = '';
                    break;
                }
                if ($secondFour == 1) {
                    $myString = new dtString();
                    $myString->NAME = 'HUMAN_TEXT';
                    $results = $myString->unserialize($this->x, $this->position);
                    $cartText = $results[0];
                    $this->position = $results[1];
                    $myString->ARRCOUNTER = $cartIndex;
                    //$this->CONTENTOBJECTS[] = $myString;
                    $textPropertyObject->addLine($myString);

                    break;
                }
                die('what is an exception?');

            default:
                die('oooops' . $terminator);
        }

        return array($textPropertyObject, $this->position);
    }

    public function getName()
    {
        return $this->NAME;
    }
}
