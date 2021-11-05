<?php
/**
 * Class dtHeader for the Fileheader
 */
class dtHeader
{
    var $NAME = 'HEADER';
    var $a = array(
        'SHeader' => 32,
        'cSaveGameVersion' => 32,
        'PackageVersion' => 32,
        'EngineVersion.Major' => 16,
        'EngineVersion.Minor' => 16,
        'EngineVersion.Patch' => 16,
        'EngineVersion.Build' => 16,
        'EngineVersion.BuildId' => 32,
        'CustomFormatVersion' => 32,
        'CustomFormatData' => 32,
    );

    var $content = '';

    /**
     * @param $fromX
     * @param $position
     * @return float|int|mixed
     */
    function unserialize($fromX, $position)
    {
        foreach ($this->a as $elem => $bits) {

            if ($elem == 'EngineVersion.BuildId') {
                $substring = mb_substr($fromX, $position, 4);
                $substringUnpacked = unpack('I', $substring)[1];

                $position += 4;
                $this->content .= $substring;
                $str = substr($fromX, $position, $substringUnpacked);
                $this->content .= $str;
                $position += $substringUnpacked;
                continue;
            }

            $substring = mb_substr($fromX, $position, $bits / 8);
            $position += $bits / 8;
            if (substr($elem, 0, 1) == 'S') {
                //$substringUnpacked = array('1' => $substring);  // GVAS
                $this->content = 'GVAS';
            } else {
                if ($bits == 16) {
                    $substringUnpacked = unpack('S', $substring);
                    $this->content .= $substring;
                }
                if ($bits == 32) {
                    $substringUnpacked = unpack('I', $substring);
                    $this->content .= $substring;
                }
            }
        }


        $dataObjects = $substringUnpacked[1];
        for ($i = 0; $i < $dataObjects; $i++) {
            $id = unpack('h*', substr($fromX, $position, 16))[1];
            $this->content .= substr($fromX, $position, 16);
            $position += 16;

            $val = unpack('I', substr($fromX, $position, 4))[1];
            $this->content .= pack('I', $val);
            $position += 4;
        }

        //$this->content .= substr($fromX, $position, 2); // ???
        $position += 2;

        return $position;
    }

    function serialize()
    {
        return $this->content;
    }

}
