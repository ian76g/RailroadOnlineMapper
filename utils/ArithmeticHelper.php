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
        $length1 = $segment1['LocationEnd']['X']-$segment1['LocationStart']['X'];
        $height1 = $segment1['LocationEnd']['Y']-$segment1['LocationStart']['Y'];
        if($length1 == 0){
            die('Edge case 1/3 not implemented');
        }
        $m1 = $height1/$length1;
        $n1 = $segment1['LocationStart']['Y']-$m1*$segment1['LocationStart']['X'];

        //         - POINT 50,80 (3)-
        $length2 = $segment2['LocationEnd']['X']-$segment2['LocationStart']['X'];
        $height2 = $segment2['LocationEnd']['Y']-$segment2['LocationStart']['Y'];
        if($length2 == 0){
            die('Edge case 2/3 not implemented');
        }
        $m2 = $height2/$length2;
        $n2 = $segment2['LocationStart']['Y']-$m2*$segment2['LocationStart']['X'];

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
        if ($this->dist( array($xIntersect, $yIntersect), $segment1['LocationEnd'], true) < $this->dist(array($xIntersect, $yIntersect) , $segment1['LocationStart'], true)) {
            $dir_s1['X'] = $segment1['LocationEnd']['X'] - $segment1['LocationStart']['X'];
            $dir_s1['Y'] = $segment1['LocationEnd']['Y'] - $segment1['LocationStart']['Y'];
            $nearestOne = $segment1['LocationEnd'];
        } else {
            $parking = $segment1['LocationStart'];
            $segment1['LocationStart'] = $segment1['LocationEnd'];
            $segment1['LocationEnd'] = $parking;
            $dir_s1['X'] = $segment1['LocationEnd']['X'] - $segment1['LocationStart']['X'];
            $dir_s1['Y'] = $segment1['LocationEnd']['Y'] - $segment1['LocationStart']['Y'];
            $nearestOne = $segment1['LocationEnd'];
        }


        $norm_dir_s1 = sqrt(pow($dir_s1['X'],2) + pow($dir_s1['Y'],2));

        $normalized_dir_s1['X'] = $dir_s1['X']/$norm_dir_s1;
        $normalized_dir_s1['Y'] = $dir_s1['Y']/$norm_dir_s1;

        if ($this->dist( array($xIntersect, $yIntersect), $segment2['LocationEnd'], true) < $this->dist(array($xIntersect, $yIntersect) , $segment2['LocationStart'], true)) {
            $dir_s1['X'] = $segment2['LocationEnd']['X'] - $segment2['LocationStart']['X'];
            $dir_s1['Y'] = $segment2['LocationEnd']['Y'] - $segment2['LocationStart']['Y'];
            $nearestOther = $segment2['LocationEnd'];
        } else {
            $parking = $segment2['LocationStart'];
            $segment2['LocationStart']=$segment2['LocationEnd'];
            $segment2['LocationEnd']=$parking;
            $dir_s1['X'] = $segment2['LocationEnd']['X'] - $segment2['LocationStart']['X'];
            $dir_s1['Y'] = $segment2['LocationEnd']['Y'] - $segment2['LocationStart']['Y'];
            $nearestOther = $segment2['LocationEnd'];
        }

        $dir_s2['X'] = $segment2['LocationEnd']['X'] - $segment2['LocationStart']['X'];
        $dir_s2['Y'] = $segment2['LocationEnd']['Y'] - $segment2['LocationStart']['Y'];


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
//        $distArray[1] = $this->dist($segment1['LocationStart'], array($xIntersect, $yIntersect), true);
        $distArray[2] = $this->dist($segment1['LocationEnd'], array($xIntersect, $yIntersect), true);
//        $distArray[3] = $this->dist($segment2['LocationStart'], array($xIntersect, $yIntersect), true);
        $distArray[4] = $this->dist($segment2['LocationEnd'], array($xIntersect, $yIntersect), true);

        asort($distArray);
        $keys = array_keys($distArray);
        $key = array_shift($keys);
        $distance = array_shift($distArray);

        // calculate orthogonal through point
        if($key == 2){
            $ortho_dir['X'] = -$normalized_dir_s1['Y'];
            $ortho_dir['Y'] = $normalized_dir_s1['X'];
            $orthoM = $ortho_dir['Y']/$ortho_dir['X'];

            $theOther = $nearestOther;
            $nearest = $nearestOne;
            $theOtherDirection['X'] = -$normalized_dir_s2['X'];
            $theOtherDirection['Y'] = -$normalized_dir_s2['Y'];
        }
        if($key == 4){
            $ortho_dir['X'] = -$normalized_dir_s2['Y'];
            $ortho_dir['Y'] = $normalized_dir_s2['X'];
            $orthoM = $ortho_dir['Y']/$ortho_dir['X'];

            $theOther = $nearestOne;
            $nearest = $nearestOther;
            $p = $m1;
            $m1=$m2;
            $m2=$p;
            $p = $n1;
            $n1=$n2;
            $n2=$p;
            $theOtherDirection['X'] = -$normalized_dir_s1['X'];
            $theOtherDirection['Y'] = -$normalized_dir_s1['Y'];
        }

        // y = m*x+n    (given is x,y,m - what is n)

if($key==1 || $key==3) die('CRAP');
//        if($key == 1){
//            $orthoN = $segment1['LocationStart']['Y']-$orthoM*$segment1['LocationStart']['X'];
//            $nearest = array($segment1['LocationStart']['X'], $segment1['LocationStart']['Y'], 'Z'=>$segment1['LocationStart']['Z']);
//        }
        if($key == 2){
            $orthoN = $segment1['LocationEnd']['Y']-$orthoM*$segment1['LocationEnd']['X'];
        }
//        if($key == 3){
//            $orthoN = $segment2['LocationStart']['Y']-$orthoM*$segment2['LocationStart']['X'];
//            $nearest = array($segment2['LocationStart']['X'], $segment2['LocationStart']['Y'], 'Z'=>$segment2['LocationStart']['Z']);
//        }
        if($key == 4){
            $orthoN = $segment2['LocationEnd']['Y']-$orthoM*$segment2['LocationEnd']['X'];
        }

        // calculate intersection of half and ortho
        // x         = (n2-n1)/(m1-m2)
        $xCircle  = ($orthoN-$halfN)/($halfM-$orthoM);
        $yCircle  = $halfM*$xCircle+$halfN;

        // radius = distance circle and nearest point
        $radius = $this->dist(array($xCircle, $yCircle), $nearest, true);

        // calculate start point of circle
        $angleStart = rad2deg(asin(max(-1,min(1,($nearest['Y']-$yCircle)/$radius))));

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


        $straight = $this->dist(array($xOut, $yOut), $theOther ,TRUE);

        $num = ($yOut-$yCircle)/$radius;
        $angleEnd = rad2deg(asin(max(-1,min(1,$num))));

        $angledelta = $angleEnd-$angleStart;
        $arclength = 2*pi()*$radius*$angledelta/360;
        $numberOfSegments =  ceil($arclength/300);
//        $numberOfSegments =  8;
//echo "curved segments: ".$numberOfSegments." \n";
        $segmentAngle = $angledelta/$numberOfSegments;
        $curvedSegmentLength = 2*pi()*$radius*$segmentAngle/360;


        $totalTrackLength = $straight + $arclength;
        $incline = $theOther['Z']-$nearest['Z']; //height difference


        $curve[] = array(round($nearest['X']), round($nearest['Y']), round($nearest['Z']));

        // totallength     traveled so far
        //-------------    ---------------
        // total height     height X

        $traveledSoFar = 0;

        if(true){
            //against the clock
            for($i=$numberOfSegments; $i>0; $i--){
                $a = $angleStart+$i*$segmentAngle;
//            echo "($a)";
                $xOnCircle = cos(deg2rad($a))*$radius;
                $yOnCircle = sin(deg2rad($a))*$radius;
                $z = $incline*$traveledSoFar/$totalTrackLength+$nearest['Z']; //need to add the height of the starting point
                $curve[] = array(round($xOnCircle+$xCircle), round($yOnCircle+$yCircle), $z);
                $traveledSoFar+=$curvedSegmentLength;
            }
        } else {
            // clockwise
            for($i=1; $i<=$numberOfSegments; $i++){
                $a = $angleStart+$i*$segmentAngle;
//            echo "($a)";
                $xOnCircle = cos(deg2rad($a))*$radius;
                $yOnCircle = sin(deg2rad($a))*$radius;
                $z = $incline*$traveledSoFar/$totalTrackLength+$nearest['Z']; //need to add the height of the starting point
                $curve[] = array(round($xOnCircle+$xCircle), round($yOnCircle+$yCircle), $z);
                $traveledSoFar+=$curvedSegmentLength;
            }
        }


        $straightSegments = ceil($straight/1000);
        $straightLength = $straight/$straightSegments;

        //$xOut, $yOut

        for($i=0; $i<$straightSegments; $i++){

            $xOnLine = $xOut + $i*$straightLength*$theOtherDirection['X'];
            $yOnLine = $yOut + $i*$straightLength*$theOtherDirection['Y'];

            $z = $incline*$traveledSoFar/$totalTrackLength+$nearest['Z'];

            $curve[] = array(round($xOnLine), round($yOnLine), $z);
            $traveledSoFar+=$straightLength;

        }

//        $curve[] = array(round($theOther['X']), round($theOther['Y']), round($theOther['Z']));




echo "<html><body></body></html><form method='get' action='../test.php'>";
        echo "Segment 1: <input name='x1' value='";
        echo $segment1['LocationStart']['X']."'>, <input name='y1' value='".$segment1['LocationStart']['Y']."'> to <input name='x2' value='";
        echo $segment1['LocationEnd']['X']."'>, <input name='y2' value='".$segment1['LocationEnd']['Y']."'><br>\n";
        echo "Segment 2: <input name='x3' value='";
        echo $segment2['LocationStart']['X']."'>, <input name='y3' value='".$segment2['LocationStart']['Y']."'> to <input name='x4' value='";
        echo $segment2['LocationEnd']['X']."'>, <input name='y4' value='".$segment2['LocationEnd']['Y']."'><br>\n";

        echo "Intersect: ";
        echo $xIntersect.', '.$yIntersect."<br>\n";

        echo "Circle: ";
        echo $xCircle.', '.$yCircle."<br>\n";

        echo "Radius: ";
        echo $radius."<br>\n";

        echo "Cirle start: ";
        echo $nearest['X'].', '.$nearest['Y']."<br>\n";

        echo "Angle start: ";
        echo $angleStart."<br>\n";

        echo "Circle end: ";
        echo $xOut.', '.$yOut."<br>\n";

        echo "Angle end: ";
        echo $angleEnd."<br>\n";

        echo "half N: ";
        echo $halfN."<br>\n";
        echo "orto N: ";
        echo $orthoN."<br>\n";

//print_r($curve);
echo "<input type='submit'></form>";
$s = 0.01;
echo '
        <svg
          id="demo-tiger"
          class="export__map-viewer"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 1000 1000"
        >
<ellipse cx="'.round($xCircle*$s).'" cy="'.round($yCircle*$s).'" rx="'.round($radius*$s).'" ry="'.round($radius*$s).'" stroke="lightgray" fill="none"/>
<ellipse cx="'.round($xIntersect*$s).'" cy="'.round($yIntersect*$s).'" rx="'.round(10).'" ry="'.round(10).'" stroke="lightgray" fill="yellow"/>
    <line stroke="blue" stroke-width="3" x1="'.$segment1['LocationStart']['X']*$s.'" x2="'.$segment1['LocationEnd']['X']*$s.'"
    y1="'.$segment1['LocationStart']['Y']*$s.'" y2="'.$segment1['LocationEnd']['Y']*$s.'" />
    <line stroke="green" stroke-width="3" x1="'.$segment2['LocationStart']['X']*$s.'" x2="'.$segment2['LocationEnd']['X']*$s.'"
    y1="'.$segment2['LocationStart']['Y']*$s.'" y2="'.$segment2['LocationEnd']['Y']*$s.'" />

    <line stroke="purple" stroke-width="1" x1="0" x2="'.round($xIntersect*$s).'"
    y1="'.round($halfN*$s).'" y2="'.round($yIntersect*$s).'" />

    <path d="M '.round($curve[0][0]*$s).','.round($curve[0][1]*$s).' ';
foreach($curve as $c){
    echo 'L '.round($c[0]*$s).','.round($c[1]*$s).' ';
}

echo '" stroke-width="2" stroke="red" fill="none" />
</svg>
</body></html>
';

        //        return array($xCircle, $yCircle, $radius);

        /**
        SplineLocationArray
        SplineTypeArray
        SplineControlPointsArray
        SplineControlPointsIndexStartArray
        SplineControlPointsIndexEndArray
        SplineSegmentsVisibilityArray
        SplineVisibilityStartArray
        SplineVisibilityEndArray
         */

    }
}
