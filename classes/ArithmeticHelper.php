<?php

class ArithmeticHelper
{

    function nearestIndustry($coords, $industryCoords)
    {
        $minDist = 800000;
        foreach ($industryCoords as $i) {
            if ($i['Type'] < 10) {
                $d = $this->dist($i['Location'], $coords);
                if ($d < $minDist) {
                    $minDist = $d;
                    $ind = $i['Type'];
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
        }

        return $name;
    }

    function dist($coords, $coords2)
    {
        $distance = sqrt(
            pow($coords[0] - $coords2[0], 2) +
            pow($coords[1] - $coords2[1], 2) +
            pow($coords[2] - $coords2[2], 2)
        );

        return $distance;
    }

}
