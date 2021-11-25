<?php


class dtStruct extends dtAbstractData
{
    var $NAME='StructProperty';

    var $name;
    var $type;
    var array $length;
    var $subType;
    var $seventeenBytes;

    public function getName()
    {
        return $this->NAME;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @param mixed $subType
     */
    public function setSubType($subType)
    {
        $this->subType = $subType;
    }

    /**
     * @param mixed $seventeenBytes
     */
    public function setSeventeenBytes($seventeenBytes)
    {
        $this->seventeenBytes = $seventeenBytes;
    }

    public function serialize()
    {
        $output = '';
        $output .= $this->name->serialize();
        $output .= $this->type->serialize();
        $output .= pack($this->length[0], $this->length[1]);
        $output .= $this->subType->serialize();
        $output .= $this->seventeenBytes;

        return $output;
    }
}