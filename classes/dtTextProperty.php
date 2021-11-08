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
    var $test = array();

    function serialize()
    {
        $output = '';
        $output .= $this->terminator;
        $output .= $this->firstFour;
        $output .= $this->secondFour;

        switch(unpack('C', $this->terminator)){
            case '1' :
                $output .= $this->unknown;
                $output .= $this->typeOfNextThing->serialize();
                $output .= $this->formatter->serialize();
                $output .= pack($this->numberOfLines[0], $this->numberOfLines[1]);

                while(sizeof($this->lines))
                {
                    $x = array_shift($this->lines);
                    $output .= $x->serialize();
                    $output .= array_shift($this->test);
                    $x = array_shift($this->lines);
                    $output .= $x->serialize();
                    array_shift($this->test); // dummy;

                }

                break;

            case '2' :

                $x = array_shift($this->lines);
                $output .= $x->serialize();
                break;

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
        $this->numberOfLines = $numberOfLines;
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