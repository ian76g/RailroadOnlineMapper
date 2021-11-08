<?php
/**
 * Class dtString
 */
class dtString extends dtAbstractData
{
    var $content;
    var $string;
    var $x;
    var $position;
    var $nullBytes;
    var $NAME = 'STRING';
    var $skipNullByteStrings = true;
    var $ARRCOUNTER = '';

    /**
     * @param $fromX
     * @param $position
     * @return array
     */
    function unserialize($fromX, $position)
    {
        $this->x = $fromX;
        $this->position = $position;
        $this->readUEString();
        $this->x = '';
        return array($this->string, $this->position);
    }

    /**
     *
     */
    function readUEString()
    {

        if (substr($this->x, $this->position, 1) == NULL) {
            $this->position++;
            $this->content = NULL;
            $this->string = NULL;
            $this->nullBytes = 0;
            return;
        }
        $value = substr($this->x, $this->position, 4);
        $this->position += 4;
        $this->content = $value;
        $value = unpack('i', $value)[1];

        if ($value == 0) {
            $this->string = null;
            $this->nullBytes = 0;
            return;
        }
        if ($value == 1) {
            $this->string = '';
            $this->nullBytes = 0;
            return;
        }
        if ($value < 0) {
            //special encoding
            $value *= -2;
            $string = mb_convert_encoding(substr($this->x, $this->position, $value), "UTF-8", "UTF-16LE");
            $this->string = $string;
            $this->content .= substr($this->x, $this->position, $value);
            $this->nullBytes =
                strlen(substr($this->x, $this->position, $value)) -
                strlen(rtrim(substr($this->x, $this->position, $value), "\0"));
            $this->position += $value;
        } else {
            $this->string = substr($this->x, $this->position, $value);
            $this->position += $value;
            $this->content .= $this->string;
            $this->nullBytes = strlen($this->string) - strlen(rtrim($this->string));
        }

        return;
    }

    /**
     * @return string
     */
    public function serialize()
    {

        if ($this->skipNullByteStrings && $this->string === NULL) {
            return NULL;
        }

        if (!$this->skipNullByteStrings && $this->string === NULL) {
            return pack('i', 0);
        }

        if (mb_detect_encoding($this->string) == 'UTF-8') {
            $data = mb_convert_encoding($this->string, "UTF-16LE", "UTF-8");
            $strLength = -(strlen($data) / 2);
            $data = pack('i', $strLength) . rtrim($data, "\0");
        } else {
            $data = pack('i', strlen($this->string)) . rtrim($this->string);
        }

        for ($i = 0; $i < $this->nullBytes; $i++) {
            $data .= hex2bin('00');
        }

        return $data;
    }

}
