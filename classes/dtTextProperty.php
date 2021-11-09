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
    var $numberOfLines;

    var $lines = array();
    var $tests = array();
    var $pack;

    function getText()
    {
        $out = '';
        foreach($this->lines as $lineOject){
            if(get_class($lineOject)=='dtString'){
                if(trim($lineOject->NAME) == 'HUMAN_TEXT') {
                    $out.=trim($lineOject->string).'<br>';
                }
            } else {
                $out.=$lineOject->getText().'<br>';
            }
        }
        return $out;
    }


    function serialize()
    {
        $output = '';
        $output .= $this->terminator;
        $output .= $this->firstFour;
        $output .= $this->secondFour;

        switch(unpack('C', $this->terminator)[1]){
            case '0' :
                // empty text
                break;

            case '1' :
                $output .= $this->unknown;
                $output .= $this->typeOfNextThing->serialize();
                $output .= $this->formatter->serialize();
                $output .= pack($this->pack, $this->numberOfLines);

                while(sizeof($this->lines))
                {
                    $x = array_shift($this->lines);
                    $output .= $x->serialize();
                    $output .= array_shift($this->tests);
                    $x = array_shift($this->lines);
                    $output .= $x->serialize();
                    array_shift($this->tests); // dummy;

                }

                break;

            case '2' :

                if(unpack('i', $this->secondFour)[1]==1){
                    $x = $this->lines[0];
                    $output .= $x->serialize();
                    break;
                }
                if(unpack('i', $this->secondFour)[1]==0){
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
    }

    /**
     * @param mixed $secondFour
     */
    public function setSecondFour($secondFour)
    {
        $this->secondFour = $secondFour;
    }



}