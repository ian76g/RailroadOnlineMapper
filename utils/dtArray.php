<?php


class dtArray extends dtAbstractData
{

    var $NAME = 'Array';

    var $ITEMTYPE;
    var $BYTE;
    var dtDynamic $COUNTER;

    var $ARRCOUNTER = false;
    var $contentElements = array();

    /**
     * @var bool | dtStruct
     */
    var $structObject = false;

    public function getName()
    {
        return $this->NAME;
    }

    public function setStructObject($structObject)
    {
        $this->structObject = $structObject;
    }

    public function serialize()
    {
        $output = '';
        $output .= $this->ITEMTYPE->serialize();
        $output .= $this->BYTE;
        $output .= $this->COUNTER->serialize();

        if($this->structObject){
            $output .= $this->structObject->serialize();
        }

        foreach($this->contentElements as $elem){
            $output .= $elem->serialize();
        }

        return $output;

    }

    public function setItemType($object)
    {
        $this->ITEMTYPE = $object;
    }

    public function setByte($byte)
    {
        $this->BYTE = $byte;
    }

    public function setCounter($object)
    {
        $this->COUNTER = $object;
    }

    public function addElement($object)
    {
        $this->contentElements[] = $object;
        $this->COUNTER->updateTo(sizeof($this->contentElements));
    }

}