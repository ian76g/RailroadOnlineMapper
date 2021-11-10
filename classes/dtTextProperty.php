<?php


class dtTextProperty extends dtAbstractData
{
    var $NAME = 'TextProperty';
    var $terminator;
    var $firstFour;
    var $secondFour;

    var $unknown;
    var $typeOfNextThing;
    var $formatter;
    var $numberOfLines = 0;

    var $lines = array();
    var $tests = array();
    var $pack = 'i';

    public function __construct($string = null)
    {
        $this->unknown = hex2bin('0000000000');
        if ($string) {
            // create formatted string with two lines
            $lN0 = new dtString('0');
            $lN0->NAME = null;
            $ln1 = new dtString('1');
            $ln1->NAME = null;
            $formatter = new dtString('{0}<br>{1}');
            $formatter->NAME = null;
            $this->typeOfNextThing = new dtString('56F8D27149CC5E2D12103BBEBFCA9097');
            $this->pack = 'i';
            $this->numberOfLines = 2;
            $this->formatter = $formatter;
            $this->terminator = pack('C', 1);
            $this->setFirstFour(pack('i', 50331648));
            $this->setSecondFour(pack('i', 8));
            $this->addLine($lN0);

            $firstTextPropObject = new dtTextProperty();
            $firstTextPropObject->terminator = pack('C', 2);
            $firstTextPropObject->firstFour = hex2bin('000000FF');
            $firstTextPropObject->secondFour = pack('i', 1);
            $firstTextPropObject->unknown = '';
            $firstTextPropObject->typeOfNextThing = new dtString();
            $firstTextPropObject->formatter = null;
            $contentText = new dtString($string);
            $firstTextPropObject->addLine($contentText);

            $this->addLine($firstTextPropObject);
            $this->addTest(pack('C', 4));

            $this->addLine($ln1);
            $this->addTest('dummy');

            $secondTextPropObject = new dtTextProperty();
            $secondTextPropObject->terminator = pack('C', 2);
            $secondTextPropObject->firstFour = hex2bin('000000FF');
            $secondTextPropObject->secondFour = pack('i', 0);
            $secondTextPropObject->unknown = '';
            $secondTextPropObject->typeOfNextThing = new dtString();
            $secondTextPropObject->formatter = null;
            $secondTextPropObject->addLine(new dtString());
            $this->addLine($secondTextPropObject);
            $this->addTest(pack('C', 4));
            $this->addTest('dummy');

        }
    }


    function getText()
    {
        $out = '';
        foreach ($this->lines as $lineOject) {
            if (get_class($lineOject) == 'dtString') {
                if (trim($lineOject->NAME) == 'HUMAN_TEXT') {
                    $out .= trim($lineOject->string) . '<br>';
                }
            } else {
                $out .= $lineOject->getText() . '<br>';
            }
        }
        return $out;
    }

    /**
     * @return string
     */
    function serialize()
    {
        $output = '';
        $output .= $this->terminator;
        $output .= $this->firstFour;
        $output .= $this->secondFour;

        switch (unpack('C', $this->terminator)[1]) {
            case '0' :
                // empty text
                break;

            case '1' :
                $output .= $this->unknown;
                $output .= $this->typeOfNextThing->serialize();
                $output .= $this->formatter->serialize();
                $output .= pack($this->pack, $this->numberOfLines);

                for ($i = 0; $i < sizeof($this->lines); $i++) {
                    $x = $this->lines[$i];
                    $output .= $x->serialize();
                    $output .= $this->tests[$i];
                    $x = $this->lines[$i + 1];
                    $output .= $x->serialize();
                    $i++;
                }

                break;

            case '2' :

                if (unpack('i', $this->secondFour)[1] == 1) {
                    $x = $this->lines[0];
                    $output .= $x->serialize();
                    break;
                }
                if (unpack('i', $this->secondFour)[1] == 0) {
                    break;
                }
        }

        return $output;
    }

    public function addLine($object)
    {
        $this->lines[] = $object;
    }

    public function addTest($test)
    {
        $this->tests[] = $test;
    }

    /**
     * @param mixed $unknown
     */
    public function setUnknown($unknown)
    {
        $this->unknown = $unknown;
    }

    /**
     * @param mixed $typeOfNextThing
     */
    public function setTypeOfNextThing($typeOfNextThing)
    {
        $this->typeOfNextThing = $typeOfNextThing;
    }

    /**
     * @param mixed $formatter
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param mixed $numberOfLines
     */
    public function setNumberOfLines($numberOfLines)
    {
        $this->pack = $numberOfLines[0];
        $this->numberOfLines = unpack($this->pack, $numberOfLines[1])[1];
    }

    /**
     * @param mixed $terminator
     */
    public function setTerminator($terminator)
    {
        $this->terminator = $terminator;
    }

    /**
     * @param mixed $firstFour
     */
    public function setFirstFour($firstFour)
    {
        $this->firstFour = $firstFour;
        $this->firstFourHR = unpack('i', $firstFour)[1];
    }

    /**
     * @param mixed $secondFour
     */
    public function setSecondFour($secondFour)
    {
        $this->secondFour = $secondFour;
        $this->secondFourHR = unpack('i', $secondFour)[1];
    }


}