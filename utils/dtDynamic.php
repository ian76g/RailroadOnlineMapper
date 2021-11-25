<?php
/**
 * Class dtDynamic
 */
class dtDynamic extends dtAbstractData
{
    private $value;
    private string $NAME;
    var $ARRCOUNTER = false;
    var $pack;

    public function __construct($name)
    {
        $this->NAME = $name;
    }

    public function getName()
    {
        return $this->NAME;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($newValue)
    {
        $this->value = $newValue;
    }

    public function serialize()
    {
        return pack($this->pack, $this->value);
    }

    public function updateTo($newValue)
    {
        $this->value = $newValue;
    }
}
