<?php

abstract class CurveCalcErrorCond
{
    const LinesAreParallel = 3;
    const ArcTooTight = 4;
}

abstract class CurveCalcLimits
{
    // minimal radius for the arc/bend circle. That depends on what both
    // the engine and the wagons are allowed to take as minimum
    const MinimumRadius = 2000;
    const EpsilonForDirectionCheck = 0.2;
}

class ArithmeticHelper
{

    var $industries;

    function nearestIndustryDistance($coords, $industryCoords = null)
    {
        $minDist = 800000;
        if (!$industryCoords) {
            $industryCoords = $this->industries;
        }
        foreach ($industryCoords as $index => $industry) {
            if ($industry['Type']) {
                $d = $this->dist($industry['Location'], $coords, true);
                if ($d < $minDist) {
                    $minDist = $d;
                }
            }
        }
        $d = $this->dist([-5000, -5000], $coords, true);
        if ($d < 10000) {
            return 0;
        }

        return $minDist;
    }

    function nearestIndustry($coords, $industryCoords = null)
    {
        $minDist = 800000;
        if (!$industryCoords) {
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
                $name = '#' . $indx;
        }

        return $name;
    }

    public function dist($coords, $coords2, $flat = false)
    {
        if ($flat) {
            $coords[2] = $coords['Z'] = $coords2['Z'] = $coords2[2] = 0;
        }
        if (isset($coords['X'])) {
            if (isset($coords2['X'])) {
                $distance = sqrt(
                    pow($coords['X'] - $coords2['X'], 2) +
                    pow($coords['Y'] - $coords2['Y'], 2) +
                    pow($coords['Z'] - $coords2['Z'], 2)
                );
            } else {
                $distance = sqrt(
                    pow($coords['X'] - $coords2[0], 2) +
                    pow($coords['Y'] - $coords2[1], 2) +
                    pow($coords['Z'] - $coords2[2], 2)
                );
            }
        } else {
            if (isset($coords2['X'])) {
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


    function vec_abs(...$args)
    {
        return array_reduce($args, 'hypot', 0);
    }


    function deal_with_error($err_cond, $err_msg)
    {
        die($err_msg);
        throw new Exception('Someone fucked it up.');
    }

    function vec_rotate($vec_x, $vec_y, $rel_angle)
    {
        return [$vec_x * cos($rel_angle) + $vec_y * sin($rel_angle),
            -$vec_x * sin($rel_angle) + $vec_y * cos($rel_angle)];
    }

// plan a curve segment + straight line between two segments.
// These segments are made into virtual lines and the crossing point
// defines the direction of where the arc will be located. Out of
// the 4 points (each segment has 2), the one closest to the crossing
// point defines the size of the arc and is used as the starting
// point for it.
    function getCurveCoordsBetweenSegments($segment1, $segment2, $bedDistance)
    {
        // replacements (i.e. remove/replace accordingly)
        $s1_end_x = $segment1['LocationEnd']['X'];
        $s1_end_y = $segment1['LocationEnd']['Y'];
        $s1_end_z = $segment1['LocationEnd']['Z'];
        $s1_start_x = $segment1['LocationStart']['X'];
        $s1_start_y = $segment1['LocationStart']['Y'];
        $s1_start_z = $segment1['LocationStart']['Z'];

        $s2_end_x = $segment2['LocationEnd']['X'];
        $s2_end_y = $segment2['LocationEnd']['Y'];
        $s2_end_z = $segment2['LocationEnd']['Z'];
        $s2_start_x = $segment2['LocationStart']['X'];
        $s2_start_y = $segment2['LocationStart']['Y'];
        $s2_start_z = $segment2['LocationStart']['Z'];
        // replacement end

        // 1) calculate crossing point between the extended segments
        //    For that they are made into linear equations y = m*x + n
        //    which are then solved. Note that vertical lines have an
        //    infinite m, so these are handled specifically
        $s1_diff_x = $s1_end_x - $s1_start_x;
        $s1_diff_y = $s1_end_y - $s1_start_y;

        $s2_diff_x = $s2_end_x - $s2_start_x;
        $s2_diff_y = $s2_end_y - $s2_start_y;

        $intersect_x = 0;
        $intersect_y = 0;
        $lines_are_parallel = false;
        $intersect_y_2 = 0;
        // make sure they are not both vertical, which also means
        // they are parallel
        if ($s1_diff_x == 0 && $s2_diff_x == 0) {
            $lines_are_parallel = true;
        }
        // first segment is vertical, which means we just project
        // along the Y axis on segment2
        elseif ($s1_diff_x == 0) {
            // determine segment2 m and n and solve it then for
            // X = s1_start_x
            $s2_m = $s2_diff_y / $s2_diff_x;
            $s2_n = $s2_start_y - $s2_m * $s2_start_x;
            $intersect_x = $s1_start_x;
            $intersect_y = $s2_m * $s1_start_x + $s2_n;
        }
        // and same thing if segment2 is vertical: project along
        // the Y axis on segment1
        elseif ($s2_diff_x == 0) {
            $s1_m = $s1_diff_y / $s1_diff_x;
            $s1_n = $s1_start_y - $s1_m * $s1_start_x;
            $intersect_x = $s2_start_x;
            $intersect_y = $s1_m * $s2_start_x + $s1_n;
        }
        // normal case, both m can be calculated "safely" (within
        // resolution of what a float value supports)
        else {
            // solve m and n for both segments
            $s1_m = $s1_diff_y / $s1_diff_x;
            $s1_n = $s1_start_y - $s1_m * $s1_start_x;
            $s2_m = $s2_diff_y / $s2_diff_x;
            $s2_n = $s2_start_y - $s2_m * $s2_start_x;
            // if m is the same, the lines are parallel.
            if ($s1_m == $s2_m) {
                $lines_are_parallel = true;
            } else {
                // calculate intersecting point
                // (1) y1 = m1*x+n1
                // (2) y2 = m2*x+n2
                // intersection has y1 = y2, resolve to x:
                // (3) m1*x+n1   = m2*x+n2
                //     m1*x      = m2*x+n2-n1
                //     m1*x-m2*x = n2-n1
                //     x*(m1-m2) = n2-n1
                //     x         = (n2-n1)/(m1-m2)
                $intersect_x = ($s2_n - $s1_n) / ($s1_m - $s2_m);
                // and y can be found by filling it in either
                // one of the line equations.
                $intersect_y = $s1_m * $intersect_x + $s1_n;
                $intersect_y_2 = $s2_m * $intersect_x + $s2_n;
            }
        }

        // FIXME handle parallel case
        // Two line segments define 2 corners of a rectangle, which
        // means the arc can be on either end of the rectangle.
        // until someone can figure out how to decide that ... leave
        // it as an unsupported corner case
        if ($lines_are_parallel) {
            deal_with_error(CurveCalcErrorCond::LinesAreParallel, "Edge case 3/3 not implemented (segments are parallel)");
            return false;
        }

        // now that we have the intersection we have to determine
        // which of the 4 points (2 per segment) is closest to the
        // intersection. For that we span vectors from the intersection
        // to the points and calculate their length. The point closest
        // determines the size and orientation of the arc

        $dist_s1_start = $this->vec_abs($s1_start_x - $intersect_x, $s1_start_y - $intersect_y);
        $dist_s1_end = $this->vec_abs($s1_end_x - $intersect_x, $s1_end_y - $intersect_y);

        $dist_s2_start = $this->vec_abs($s2_start_x - $intersect_x, $s2_start_y - $intersect_y);
        $dist_s2_end = $this->vec_abs($s2_end_x - $intersect_x, $s2_end_y - $intersect_y);

        // - near (x,y) is the point on the arc/circle which is the
        //   starting point for the arc.
        // - far_off (x,y) is the point the arc "bends" to, but is
        //   most likely not located on the circle, but on a tangent
        //   of the circle.
        // - far (x,y) is the point on the arc where the tangent to
        //   far_off (x,y) starts from. Introduced later.

        // first check with segment1 start and end point
        if ($dist_s1_start < $dist_s1_end) {
            $dist_near = $dist_s1_start;
            $near_x = $s1_start_x;
            $near_y = $s1_start_y;
            $near_z = $s1_start_z;
            $near_prev_x = $s1_end_x;
            $near_prev_y = $s1_end_y;
            $near_prev_z = $s1_end_z;
            $far_off_x = $s1_start_x;
            $far_off_y = $s1_start_y;
            $far_off_z = $s1_start_z;
            $dist_far_off = $dist_s1_start;
        } else {
            $dist_near = $dist_s1_end;
            $near_x = $s1_end_x;
            $near_y = $s1_end_y;
            $near_z = $s1_end_z;
            $near_prev_x = $s1_start_x;
            $near_prev_y = $s1_start_y;
            $near_prev_z = $s1_start_z;
            $far_off_x = $s1_end_x;
            $far_off_y = $s1_end_y;
            $far_off_z = $s1_end_z;
            $dist_far_off = $dist_s1_end;
        }
        // then check with segment2 start and end point
        // to see if things are actually on the "short" end
        // there
        $s2_short_end = false;
        if ($dist_s2_start < $dist_near) {
            $dist_near = $dist_s2_start;
            $near_x = $s2_start_x;
            $near_y = $s2_start_y;
            $near_z = $s2_start_z;
            $near_prev_x = $s2_end_x;
            $near_prev_y = $s2_end_y;
            $near_prev_z = $s2_end_z;
            $s2_short_end = true;
        }
        if ($dist_s2_end < $dist_near) {
            $dist_near = $dist_s2_end;
            $near_x = $s2_end_x;
            $near_y = $s2_end_y;
            $near_z = $s2_end_z;
            $near_prev_x = $s2_start_x;
            $near_prev_y = $s2_start_y;
            $near_prev_z = $s2_start_z;
            $s2_short_end = true;
        }

        // if s2 is on the short end, the far end is already
        // set. Otherwise the far end is on whatever is closest
        // on s2
        if (!$s2_short_end) {
            if ($dist_s2_start < $dist_s2_end) {
                $far_off_x = $s2_start_x;
                $far_off_y = $s2_start_y;
                $far_off_z = $s2_start_z;
                $dist_far_off = $dist_s2_start;
            } else {
                $far_off_x = $s2_start_x;
                $far_off_y = $s2_start_y;
                $far_off_z = $s2_start_z;
                $dist_far_off = $dist_s2_start;
            }
        }
        // at this point we have near (x,y) as a point and
        // dist_near as the distance from the intersection,
        // as well as far_off (x,y) as a point and dist_far_off
        // as the distance from the intersection

        ///////////////////////////////////////////////////////////////
        // NOTE: we drop reference to s1 and s2 here, but this should be
        //       kept tracked probably. Technically "near" and "far_off"
        //       can be references to segment1/2 start/end instead.
        //       This becomes paramount the moment a Z coordinate needs
        //       to be handled (which can either be interpolated as
        //       well or intersected with whatever is "ground").
        ///////////////////////////////////////////////////////////////

        // to determine the center of the circle which defines the arc:
        // - "near" distance is a circle around the intersection which
        //   crosses the intersecting lines, through the "near" point
        //   on one and through the "far" point which lies on the same
        //   line as the "far_off" point
        // - this circle also goes through the center of the circle
        //   which defines the arc
        // - drawing a line from the intersection through the center of
        //   the arc/bend circle, it will also cut a line from "near"
        //   to "far" (not "far_off"!) in half, which we can use
        //   as a helper to determine the center of the arc/bend circle

        $far_x = ($far_off_x - $intersect_x) / $dist_far_off * $dist_near + $intersect_x;
        $far_y = ($far_off_y - $intersect_y) / $dist_far_off * $dist_near + $intersect_y;

        // calculate temporary center which is halfway on the line between
        // "near" and "far" point. The real center is outside of the circle
        // drawn around the intersection with the radius of "dist_near" (distance
        // between near and intersection). The amount can be calculated using
        // a² + b² = c² where c = a + k and b as the far end is defined by
        // the angle between a and c: b = sin(angle) * (a+k). With that we get
        // a² + sin(angle)² * (a+k)² = (a+k)² where only one variable (k) is
        // unknown and can be solved. a is the radius of the circle around
        // the intersection, k is the "addon" to the actual arc/bend center,
        // both being distance values. angle is half the angle between
        // the two vectors which span the triangle to contain the circle
        // (near and far together with intersection).

        // pointer from intersection to potential center of the arc/bend
        // center. These are vector coords, i.e. relative, not absolute
        $vec_near_far_half_x = ($far_x - $near_x) / 2 + $near_x - $intersect_x;
        $vec_near_far_half_y = ($far_y - $near_y) / 2 + $near_y - $intersect_y;
        // distance of the vector so it can be normalized
        $dist_tmp_center = $this->vec_abs($vec_near_far_half_x, $vec_near_far_half_y);
//    echo("half(far-near) = (" . $vec_near_far_half_x . ", " . $vec_near_far_half_y . "), distance/len = " . $dist_tmp_center . "\n");

        // The angle between two vectors can be determined via
        //   cos a = (vec1 * vec2) / (abs(vec1) * abs(vec2))
        // but there is no direction with that angle. See down
        // below how this is being handled.
        $vec_to_near_x = $near_x - $intersect_x;
        $vec_to_near_y = $near_y - $intersect_y;
        $vec_to_far_x = $far_x - $intersect_x;
        $vec_to_far_y = $far_y - $intersect_y;
        $cos_a_orig = ($vec_to_near_x * $vec_to_far_x + $vec_to_near_y * $vec_to_far_y) /
            ($this->vec_abs($vec_to_near_x, $vec_to_near_y) *
                $this->vec_abs($vec_to_far_x, $vec_to_far_y));
        $intersection_angle = acos($cos_a_orig);

        // now that we have the full angle we can calculate the center
        // of the actual arc as outlined above
        $dist_to_arc_center = sqrt($dist_near * $dist_near / (1 - pow(sin($intersection_angle / 2), 2)));
//    echo("dist_to_arc_center = " . $dist_to_arc_center . "\n");

        $arc_center_x = $vec_near_far_half_x / $dist_tmp_center * $dist_to_arc_center + $intersect_x;
        $arc_center_y = $vec_near_far_half_y / $dist_tmp_center * $dist_to_arc_center + $intersect_y;

        // arc/bend radius
        $arc_radius = $this->vec_abs($near_x - $arc_center_x, $near_y - $arc_center_y);
        // sum of angles in a triangle = 180°. One is 90°, one is half of the
        // angle between the two lines crossing at the intersection. Both
        // 90° = pi/2, the other is angle/2, the final angle we are interested
        // in is *2, so we skip the /2 for the parts.
        //
        $arc_angle = pi() - $intersection_angle;

        $curveLength = $arc_angle * $arc_radius;

        // FIXME handle too tight of a radius of the bend.
        //       This depends on the material which is on the track,
        //       some can take tighter bends, others less so ...
        if ($arc_radius < CurveCalcLimits::MinimumRadius) {
            $this->deal_with_error(CurveCalcErrorCond::ArcTooTight, "The curve is bending too tight to make a viable bend.");
            return false;
        }


        // As noted above, $alpha is direction less. Instead of acos()
        // atan2() can be used, which sorts things into quadrants and an
        // icky set of if-then-else checks. To shortcut this mess, having
        // the absolute angle, there are only 2 ways to go, and we check
        // which one gets close from the near to the far point.

        // rotation of a vector (technically turning coordinate system):
        // rot_x = x * cos(a) + y * sin(a)
        // rot_y = -x * sin(a) + y * cos(a)
        // we will vary between a and -a and see which ones "hits" the
        // far point and use that as direction (a -> 1, -a -> -1)
        $vec_arc_near_x = $near_x - $arc_center_x;
        $vec_arc_near_y = $near_y - $arc_center_y;

        [$test_vec_x, $test_vec_y] = $this->vec_rotate($vec_arc_near_x, $vec_arc_near_y, $arc_angle);
        $epsilon = $this->vec_abs($test_vec_x + $arc_center_x - $far_x, $test_vec_y + $arc_center_y - $far_y);

        [$test_vec_x, $test_vec_y] = $this->vec_rotate($vec_arc_near_x, $vec_arc_near_y, -$arc_angle);
        $epsilon_n = $this->vec_abs($test_vec_x + $arc_center_x - $far_x, $test_vec_y + $arc_center_y - $far_y);

//    echo("epsilon = " . $epsilon . ", epsilon_n = " . $epsilon_n . "\n");

        // negative is closer than positive? change direction.
        // FIXME in theory this could be used for validation of the results, i.e.
        //       if both results are too far off, something in the calculation
        //       went off. The epsilon here depends a bit on the input coords,
        //       which means the larger the input coord numbers, the higher both
        //       epsilon values go, even the one which should ideally be 0.
        if ($epsilon_n < $epsilon) {
            $arc_angle = -$arc_angle;
        }

        // at this point we have:
        // - "near_[x|y]"            point where we start
        // - "far_[x|y]"             point where we end up with the arc/bend
        // - "far_off_[x|y]"         point of the "off" segment, needs a straight
        //                           section of track segment(s) from "far" to
        //                           that point
        // - "arc_center_[x|y]"      center of the arc/bend circle
        // - "arc_radius"            radius of the arc/bend
        // - "vec_to_near_[x|y]"     vector from "near" to "arc_center"
        // - "alpha"                 angle with a direction sign for rotation.
        //                           Rotation is centered at "arc_center(x,y)",
        //                           i.e. using "vec_to_near" and this directional
        //                           angle for rotation to determine segments.

//    echo("<pre>\n");
//    echo("intersect = (" . $intersect_x . ", " . $intersect_y . "), intersect_y_2 = " . $intersect_y_2 . "\n");
//    echo("segment1:\n");
//    echo("  start = (" . $s1_start_x . ", " . $s1_start_y . "), distance = " . $dist_s1_start . "\n");
//    echo("  end   = (" . $s1_end_x . ", " . $s1_end_y . "), distance = " . $dist_s1_end . "\n");
//    if ($s1_diff_x != 0) {
//        echo("  m = " . $s1_m . ", n = " . $s1_n . "\n");
//    }
//    echo("segment2:\n");
//    echo("  start = (" . $s2_start_x . ", " . $s2_start_y . "), distance = " . $dist_s2_start . "\n");
//    echo("  end   = (" . $s2_end_x . ", " . $s2_end_y . "), distance = " . $dist_s2_end . "\n");
//    if ($s2_diff_x != 0) {
//        echo("  m = " . $s2_m . ", n = " . $s2_n . "\n");
//    }
//    echo("near = (" . $near_x . ", " . $near_y . "), distance = " . $dist_near . "\n");
//    echo("far_off = (" . $far_off_x . ", " . $far_off_y . "), distance = " . $dist_far_off . "\n");
//    echo("far = (" . $far_x . ", " . $far_y . ")\n");
//    echo("alpha = " . $arc_angle . ", cos_a_orig = " . $cos_a_orig . "\n");
//    echo("arc_center = (" . $arc_center_x . ", " . $arc_center_y . "), radius = " . $arc_radius . "\n");
//
//echo '</pre>';
//echo "\n-->\n";
        // NOTE: calcs will cause errors of all sorts, especially such
        //       more sophisticated stuff. floats are by no means sufficient
        //       for things like this. The very least which should be done:
        //       Connect the last circle segment to "far" point (instead of
        //       calculating it), and then make the line to "far_off".


        // debug SVG output
        $num_segments = 8;
        // 300
        $num_segments = ceil($curveLength / 500);


        $alpha_per_segment = $arc_angle / $num_segments;
        $curveSegmentLength = $curveLength / $num_segments;
        $curve = array();
        $curve2 = array();
        $curve[] = array($near_prev_x, $near_prev_y, $near_prev_z);
        $curve2[] = array($near_prev_x, $near_prev_y, round($near_prev_z) - $bedDistance);
        $curve[] = array($near_x, $near_y, $near_z);
//    $curve2[] = array($near_x, $near_y, round($near_z)-$bedDistance);


        $straight = $this->vec_abs($far_x - $far_off_x, $far_y - $far_off_y);

        $totalTrackLength = $straight + $curveLength;

        $incline = $far_off_z - $near_z; //height difference


        // totallength     traveled so far
        //-------------    ---------------
        // total height     height X

        $traveledSoFar = 0;

        for ($i = 1; $i < $num_segments; $i++) {
            [$tmp_vec_x, $tmp_vec_y] = $this->vec_rotate($near_x - $arc_center_x, $near_y - $arc_center_y, $alpha_per_segment * $i);
            $tmp_vec_x += $arc_center_x;
            $tmp_vec_y += $arc_center_y;
            $z = $incline * $traveledSoFar / $totalTrackLength + $near_z; //need to add the height of the starting point
            if (!($i % 3)) {
                $curve2[] = array(round($tmp_vec_x), round($tmp_vec_y), round($z) - $bedDistance);
            }
            $curve[] = array(round($tmp_vec_x), round($tmp_vec_y), round($z));
            $traveledSoFar += $curveSegmentLength;
//        $svg_output .= " L " . round($tmp_vec_x * $scale) . "," . round($tmp_vec_y * $scale);
        }
        $z = $incline * $traveledSoFar / $totalTrackLength + $near_z;
//    $curve[] = array($far_x, $far_y, $z);
//    $svg_output .= " L " . $far_x * $scale . "," . $far_y * $scale;

        $traveledSoFar += $curveSegmentLength;

        $straightSegments = ceil($straight / 900);
        $straightLength = $straight / $straightSegments;


        $dist_straight_norm_vec = $this->vec_abs($far_off_x - $far_x, $far_off_y - $far_y);
        $straight_vec_x = ($far_off_x - $far_x) / $dist_straight_norm_vec;
        $straight_vec_y = ($far_off_y - $far_y) / $dist_straight_norm_vec;

        //$xOut, $yOut

        for ($i = 0; $i <= $straightSegments; $i++) {
            $xOnLine = $far_x + $i * $straightLength * $straight_vec_x;
            $yOnLine = $far_y + $i * $straightLength * $straight_vec_y;

            $z = $incline * $traveledSoFar / $totalTrackLength + $near_z;
//        $svg_output .= " L " . round($xOnLine * $scale) . "," . round($yOnLine * $scale);

            $curve[] = array(round($xOnLine), round($yOnLine), round($z));
            if ($i && !($i % 5)) {
                $curve2[] = array(round($xOnLine), round($yOnLine), round($z) - $bedDistance);
            }
            $traveledSoFar += $straightLength;
        }

        $curve2[] = array(round($xOnLine), round($yOnLine), round($z) - $bedDistance);
//    $svg_output .= "\" />\n";
//    $svg_output .= "</svg>\n";
//    echo($svg_output);
        // report success
        return array($curve, $curve2);
    }


//$sp0 = $_GET['sp0'];
//$se0 = $_GET['se0'];
//$sp1 = $_GET['sp1'];
//$se1 = $_GET['se1'];

// sp0=1158&se0=17&sp1=56&se1=10
    /*
    $sp0 = 1145;
    $se0 = 72;
    $sp1 = 1145;
    $se1 = 28;
    */

//$segment1 = $x['Splines'][$sp0]['Segments'][$se0];
//$segment2 = $x['Splines'][$sp1]['Segments'][$se1];
//echo "<!--\n";
//echo "##################\n";
//print_r($segment1);
//echo "##################\n";
//print_r($segment2);
//echo "##################\n";


    /**
     * SplineLocationArray                 -- array of Vector of SplineStart Posistions
     * SplineTypeArray                     -- 4 (track)
     * SplineControlPointsArray            -- all coordinates of spline segment starts
     * SplineControlPointsIndexStartArray  -- array of pointer to index of start segment
     * SplineControlPointsIndexEndArray    -- array of pointer to end segment
     * SplineSegmentsVisibilityArray       -- array of bool 1
     * SplineVisibilityStartArray          -- add index of spline start
     * SplineVisibilityEndArray            -- add index of spline end
     */

    public function estimateHeight($x, $y)
    {

    }

}
