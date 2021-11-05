<?php
/**
 * Class dtDynamic
 */
class dtDynamic
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
