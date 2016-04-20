<?php

namespace GGS\SVGGraph;

/**
 * Class for axis with +ve on both sides of zero.
 */
class AxisDoubleEnded extends Axis
{
    /**
     * Constructor calls Axis constructor with 1/5 length.
     *
     * @param $length
     * @param $max_val
     * @param $min_val
     * @param $min_unit
     * @param $fit
     * @param $units_before
     * @param $units_after
     * @param $decimal_digits
     * @param $label_callback
     *
     * @throws \Exception
     */
    public function __construct(
        $length,
        $max_val,
        $min_val,
        $min_unit,
        $fit,
        $units_before,
        $units_after,
        $decimal_digits,
        $label_callback
    ) {
        if ($min_val < 0) {
            throw new \Exception('Negative value for double-ended axis');
        }
        parent::__construct(
            $length / 2,
            $max_val,
            $min_val,
            $min_unit,
            $fit,
            $units_before,
            $units_after,
            $decimal_digits,
            $label_callback
        );
    }

    /**
     * Returns the distance along the axis where 0 should be.
     */
    public function Zero()
    {
        return $this->zero = $this->length;
    }

    /**
     * Returns the grid points as an array of GridPoints.
     *
     * @param $min_space
     * @param $start
     *
     * @return array
     * @throws \Exception
     */
    public function GetGridPoints($min_space, $start)
    {
        $points     = parent::GetGridPoints($min_space, $start);
        $new_points = array();
        $z          = $this->Zero();
        foreach ($points as $p) {
            $new_points[] = new GridPoint($p->position + $z, $p->text, $p->value);
            if ($p->value != 0) {
                $new_points[] = new GridPoint((2 * $start) + $z - $p->position, $p->text, $p->value);
            }
        }

        usort(
            $new_points,
            $this->direction < 0
                ?
                function($a, $b) {
                    return $b->position - $a->position;
                }
                :
                function($a, $b) {
                    return $a->position - $b->position;
                }
        );

        return $new_points;
    }

    /**
     * Returns the grid subdivision points as an array.
     *
     * @param $min_space
     * @param $min_unit
     * @param $start
     * @param $fixed
     *
     * @return array
     * @throws \Exception
     */
    public function GetGridSubdivisions($min_space, $min_unit, $start, $fixed)
    {
        $divs     = parent::GetGridSubdivisions($min_space, $min_unit, $start, $fixed);
        $new_divs = array();
        $z        = $this->Zero();
        foreach ($divs as $d) {
            $new_divs[] = new GridPoint($d->position + $z, '', 0);
            $new_divs[] = new GridPoint((2 * $start) + $z - $d->position, '', 0);
        }

        return $new_divs;
    }
}
