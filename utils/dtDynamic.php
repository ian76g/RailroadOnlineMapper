<?php
/**
 * Class dtDynamic
 */
class dtDynamic extends dtAbstractData
{
    var $value;
    var $NAME = 'dynamic';
    var $ARRCOUNTER = false;
    var $pack;

    function serialize()
    {
        return pack($this->pack, $this->value);
    }
}
