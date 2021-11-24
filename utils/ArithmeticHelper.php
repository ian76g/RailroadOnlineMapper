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

    public function dist($coords, $coords2, $flat = false)
    {
        if($flat){
            $coords[2] = $coords['Z'] = $coords2['Z'] = $coords2[2] = 0;
        }
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

    function getCurveCoordsBetweenSegments($segment1, $segment2)
    {
        // calculate formulas for segment 1 and segment 2
        // y = m*x+n     x,y given 2 times - can be solved
        $length1 = $segment1[1]['X']-$segment1[0]['X'];
        $height1 = $segment1[1]['Y']-$segment1[0]['Y'];
        if($length1 == 0){
            die('Edge case 1/3 not implemented');
        }
        $m1 = $height1/$length1;
        $n1 = $segment1[0]['Y']-$m1*$segment1[0]['X'];

        $length2 = $segment2[1]['X']-$segment2[0]['X'];
        $height2 = $segment2[1]['Y']-$segment2[0]['Y'];
        if($length2 == 0){
            die('Edge case 2/3 not implemented');
        }
        $m2 = $height2/$length2;
        $n2 = $segment2[0]['Y']-$m2*$segment2[0]['X'];

        // calculate intersecting point
        // y = m1*x+n1
        // y = m2*x+n2
        // m1*x+n1   = m2*x+n2
        // m1*x      = m2*x+n2-n1
        // m1*x-m2*x = n2-n1
        // x*(m1-m2) = n2-n1
        // x         = (n2-n1)/(m1-m2)
        if($m1 == $m2){
            die('Edge case 3/3 not implemented');
        }
        $xIntersect  = ($n2-$n1)/($m1-$m2);
        $yIntersect  = $m1*$xIntersect+$n1;
        $yIntersect  = $m2*$xIntersect+$n2;

        // calculate half m
        $halfM = ($m1+$m2)/2;

        // y = m*x+n    (given is x,y,m - what is n)
        $halfN = $yIntersect-$halfM*$xIntersect;

        // calculate diffs between 4 given points and the intersection - find closest point
        $distArray[1] = $this->dist($segment1[0], array($xIntersect, $yIntersect), true);
        $distArray[2] = $this->dist($segment1[1], array($xIntersect, $yIntersect), true);
        $distArray[3] = $this->dist($segment2[0], array($xIntersect, $yIntersect), true);
        $distArray[4] = $this->dist($segment2[1], array($xIntersect, $yIntersect), true);

        asort($distArray);
        $keys = array_keys($distArray);
        $key = array_shift($keys);
        $distance = array_shift($distArray);

        // calculate orthogonal through point
        if($key == 1 || $key == 2){
            $orthoM = 1/$m1;
        }
        if($key == 3 || $key == 4){
            $orthoM = 1/$m2;
        }

        // y = m*x+n    (given is x,y,m - what is n)
        if($key == 1){
            $orthoN = $segment1[0]['Y']-$orthoM*$segment1[0]['X'];
            $nearest = array($segment1[0]['X'], $segment1[0]['Y']);
        }
        if($key == 2){
            $orthoN = $segment1[1]['Y']-$orthoM*$segment1[1]['X'];
            $nearest = array($segment1[1]['X'], $segment1[1]['Y']);
        }
        if($key == 3){
            $orthoN = $segment2[0]['Y']-$orthoM*$segment2[0]['X'];
            $nearest = array($segment2[0]['X'], $segment2[0]['Y']);
        }
        if($key == 4){
            $orthoN = $segment2[1]['Y']-$orthoM*$segment2[1]['X'];
            $nearest = array($segment2[1]['X'], $segment2[1]['Y']);
        }

        // calculate intersection of half and ortho
        // x         = (n2-n1)/(m1-m2)
        $xCircle  = ($orthoN-$halfN)/($halfM-$orthoM);
        $yCircle  = $halfM*$xCircle+$halfN;

        // radius = distance circle and nearest point
        $radius = $this->dist(array($xCircle, $yCircle), $nearest, true);

        // calculate exit point of circle
        $angleStart = asin(($nearest[1]-$yCircle)/$radius);

        // x²+y² = d²  // d = distance is given
        // y=m*x+n     // m and n are given

        // length to point of track 2
        $length=sqrt($xIntersect+$yIntersect);
        $shortLength = $length-$distance;

        // xIntersect to l  = unknownX to short
        $xOut = $xIntersect*$shortLength/$length;
        $yOut = $yIntersect*$shortLength/$length;

        $num = ($yOut-$yCircle)/$radius;
        $angleEnd = asin($num);
echo "<pre>";
        echo "Segment 1: ";
        echo $segment1[0]['X'].', '.$segment1[0]['Y']." to ";
        echo $segment1[1]['X'].', '.$segment1[1]['Y']."\n";
        echo "Segment 2: ";
        echo $segment2[0]['X'].', '.$segment2[0]['Y']." to ";
        echo $segment2[1]['X'].', '.$segment2[1]['Y']."\n";

        echo "Intersect: ";
        echo $xIntersect.', '.$yIntersect."\n";

        echo "Circle: ";
        echo $xCircle.', '.$yCircle."\n";

        echo "Radius: ";
        echo $radius."\n";

        echo "Cirle start: ";
        echo $nearest[0].', '.$nearest[1]."\n";

        echo "Angle start: ";
        echo $angleStart."\n";

        echo "Circle end: ";
        echo $xOut.', '.$yOut."\n";

        echo "Angle end: ";
        echo $angleEnd."\n";


        //        return array($xCircle, $yCircle, $radius);

    }
}
