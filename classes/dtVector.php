<?php
/**
 * Class dtVector
 */
class dtVector
{
    var $content;
    var $NAME='Vector';
    var $ARRCOUNTER = '';

    /**
     * @param $fromX
     * @param $position
     * @return array
     */
    public function unserialize($fromX, $position)
    {
        $sub = substr($fromX, $position, 4);
        if(strlen($sub)!=4) {
            echo "aarg";var_dump($position);die();
        }
        $x = unpack('g', $sub)[1];
        $position += 4;

        $y = unpack('g', substr($fromX, $position, 4))[1];
        $position += 4;

        $z = unpack('g', substr($fromX, $position, 4))[1];
        $position += 4;

        $this->content = array($x, $y, $z);


        return array($position, $this->content);

    }

    /**
     * @return string
     */
    public function serialize()
    {
        $content = '';
        foreach ($this->content as $val){
            $content .= pack('g', $val);
        }

        return $content;
    }

}