<?php

class ArithmeticHelper
{

    var $industries;

    function nearestIndustry($coords, $industryCoords = null)
    {
        $minDist = 800000;
        if(!$industryCoords){
            $industryCoords = $this->industries;
        }
        foreach ($industryCoords as $index => $industry) {
            if ($industry['Type']) {
                $d = $this->dist($industry['Location'], $coords);
                if ($d < $minDist) {
                    $minDist = $d;
                    $ind = $industry['Type'];
                    $indx = $index;
                }
            }
        }

        switch ($ind) {
            case '1':
                $name = 'Logging Camp';
                break;
            case '2':
                $name = 'Sawmill';
                break;
            case '3':
                $name = 'Smelter';
                break;
            case '4':
                $name = 'Ironworks';
                break;
            case '5':
                $name = 'Oilfield';
                break;
            case '6':
                $name = 'Refinery';
                break;
            case '7':
                $name = 'Coal Mine';
                break;
            case '8':
                $name = 'Iron Mine';
                break;
            case '9':
                $name = 'Freight Depot';
                break;
            default:
                $name = '#'.$indx;
        }

        return $name;
    }

    public function dist($coords, $coords2)
    {
        if(isset($coords['X'])){
            if(isset($coords2['X'])){
                $distance = sqrt(
                    pow($coords['X'] - $coords2['X'], 2) +
                    pow($coords['Y'] - $coords2['Y'], 2) +
                    pow($coords['Z'] - $coords2['Y'], 2)
                );
            } else {
                $distance = sqrt(
                    pow($coords['X'] - $coords2[0], 2) +
                    pow($coords['Y'] - $coords2[1], 2) +
                    pow($coords['Z'] - $coords2[2], 2)
                );
            }
        } else {
            if(isset($coords2['X'])){
                $distance = sqrt(
                    pow($coords[0] - $coords2['X'], 2) +
                    pow($coords[1] - $coords2['Y'], 2) +
                    pow($coords[2] - $coords2['Z'], 2)
                );
            } else {
                $distance = sqrt(
                    pow($coords[0] - $coords2[0], 2) +
                    pow($coords[1] - $coords2[1], 2) +
                    pow($coords[2] - $coords2[2], 2)
                );
            }
        }

        return $distance;
    }

}
