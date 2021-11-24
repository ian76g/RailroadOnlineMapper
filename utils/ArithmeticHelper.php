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

        //         - POINT 50,80 (3)-
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

        //Segment 1: (1)10, 100    to    (2) 20, 100
        //Segment 2: (3)50, 80     to    (4) 40, 60
        if ($this->dist( array($xIntersect, $yIntersect), $segment1[1], true) < $this->dist(array($xIntersect, $yIntersect) , $segment1[0], true)) {
            $dir_s1['X'] = $segment1[1]['X'] - $segment1[0]['X'];
            $dir_s1['Y'] = $segment1[1]['Y'] - $segment1[0]['Y'];
            $nearestOne = $segment1[1];
        } else {
            $dir_s1['X'] = $segment1[0]['X'] - $segment1[1]['X'];
            $dir_s1['Y'] = $segment1[0]['Y'] - $segment1[1]['Y'];
            $nearestOne = $segment1[0];
        }


        $norm_dir_s1 = sqrt(pow($dir_s1['X'],2) + pow($dir_s1['Y'],2));

        $normalized_dir_s1['X'] = $dir_s1['X']/$norm_dir_s1;
        $normalized_dir_s1['Y'] = $dir_s1['Y']/$norm_dir_s1;

        if ($this->dist( array($xIntersect, $yIntersect), $segment2[1], true) < $this->dist(array($xIntersect, $yIntersect) , $segment2[0], true)) {
            $dir_s1['X'] = $segment2[1]['X'] - $segment2[0]['X'];
            $dir_s1['Y'] = $segment2[1]['Y'] - $segment2[0]['Y'];
            $nearestOther = $segment2[1];
        } else {
            $dir_s1['X'] = $segment2[0]['X'] - $segment2[1]['X'];
            $dir_s1['Y'] = $segment2[0]['Y'] - $segment2[1]['Y'];
            $nearestOther = $segment2[0];
        }

        $dir_s2['X'] = $segment2[1]['X'] - $segment2[0]['X'];
        $dir_s2['Y'] = $segment2[1]['Y'] - $segment2[0]['Y'];


        $norm_dir_s2 = sqrt(pow($dir_s2['X'],2) + pow($dir_s2['Y'],2));

        $normalized_dir_s2['X'] = $dir_s2['X']/$norm_dir_s2;
        $normalized_dir_s2['Y'] = $dir_s2['Y']/$norm_dir_s2;

        $bisecting_line_dir['X'] = $normalized_dir_s1['X'] + $normalized_dir_s2['X'];
        $bisecting_line_dir['Y'] = $normalized_dir_s1['Y'] + $normalized_dir_s2['Y'];


        // calculate half m
        //n = y - m * x
        $halfM = $bisecting_line_dir['Y']/$bisecting_line_dir['X'];

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
            $ortho_dir['X'] = -$normalized_dir_s1['Y'];
            $ortho_dir['Y'] = $normalized_dir_s1['X'];
            $orthoM = $ortho_dir['Y']/$ortho_dir['X'];

            $theOther = $nearestOther;
            $theOtherDirection = -$normalized_dir_s2;
        }
        if($key == 3 || $key == 4){
            $ortho_dir['X'] = -$normalized_dir_s2['Y'];
            $ortho_dir['Y'] = $normalized_dir_s2['X'];
            $orthoM = $ortho_dir['Y']/$ortho_dir['X'];

            $theOther = $nearestOne;
            $theOtherDirection = -$normalized_dir_s1;
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

        // calculate start point of circle
        $angleStart = rad2deg(asin(max(-1,min(1,($nearest[1]-$yCircle)/$radius))));

        // x²+y² = d²  // d = distance is given
        // y=m*x+n     // m and n are given
        //a = 1-m²
        $a = 1-pow($m1,2);
        //b = 2*m*n - 2*xc - 2*m*yc
        $b = 2*$m1*$n1 - 2*$xCircle - 2*$m1*$yCircle;

        //c = xc² + n² - 2*n*yc + yc² - d²
        $c = pow($xCircle,2)+pow($n1,2) - (2*$n1*$yCircle) + pow($yCircle,2) - pow($radius, 2);

        $d = pow($b, 2) - 4*$a*$c;
        //x = -b/(2a)
        $xOut = -($b)/(2*$a);
        //y = m. (-b/(2a)) + n
        $yOut = $m1*($xOut)+ $n1;


        $num = ($yOut-$yCircle)/$radius;
        $angleEnd = rad2deg(asin(max(-1,min(1,$num))));

        $angledelta = $angleEnd-$angleStart;
        $arclength = 2*pi()*$radius*$angledelta/360;
        $numberOfSegments =  ceil($arclength/3);
        $segmentAngle = $angledelta/$numberOfSegments;
        $curvedSegmentLength = 2*pi()*$radius*$segmentAngle/360;

        $straight = $this->dist(array($xOut, $yOut), $theOther ,TRUE);

        $totalTrackLength = $straight + $arclength;
        $incline = $theOther['Z']-$nearest['Z']; //height difference


        // totallength     traveled so far
        //-------------    ---------------
        // total height     height X

        $traveledSoFar = 0;
        for($i=0; $i<$numberOfSegments; $i++){
            $a = $angleStart+$i*$segmentAngle;
            $xOnCircle = cos($a)*$radius;
            $yOnCircle = sin($a)*$radius;
            $z = $incline*$traveledSoFar/$totalTrackLength+$nearest['Z']; //need to add the height of the starting point
            $curve[] = array($xOnCircle, $yOnCircle, $z);
            $traveledSoFar+=$curvedSegmentLength;
        }

        $straightSegments = ceil($straight/10);
        $straightLength = $straight/$straightSegments;

        //$xOut, $yOut

        for($i=0; $i<$straightSegments; $i++){

            $xOnLine = $xOut + $i*$straightLength*$theOtherDirection['X'];
            $yOnLine = $yOut + $i*$straightLength*$theOtherDirection['Y'];

            $z = $incline*$traveledSoFar/$totalTrackLength+$nearest['Z'];

            $curve[] = array($xOnLine, $yOnLine, $z);
            $traveledSoFar+=$straightLength;

        }

        $curve[] = array($theOther['X'], $theOther['Y'], $theOther['Z']);




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

        echo "half N: ";
        echo $halfN."\n";
        echo "orto N: ";
        echo $orthoN."\n";


        //        return array($xCircle, $yCircle, $radius);

    }
}
