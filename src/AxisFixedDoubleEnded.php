<?php

namespace GGS\SVGGraph;

/**
 * Axis with fixed measurements.
 */
class AxisFixedDoubleEnded extends AxisDoubleEnded
{
    protected $step;

    public function __construct(
        $length,
        $max_val,
        $min_val,
        $step,
        $units_before,
        $units_after,
        $decimal_digits,
        $label_callback
    ) {
        parent::__construct(
            $length,
            $max_val,
            $min_val,
            1,
            false,
            $units_before,
            $units_after,
            $decimal_digits,
            $label_callback
        );
        $this->step = $step;
    }

    /**
     * Calculates a grid based on min, max and step
     * min and max will be adjusted to fit step.
     *
     * @param      $min
     * @param bool $round_up
     *
     * @return float
     * @throws \Exception
     */
    protected function Grid($min, $round_up = false)
    {
        // if min and max are the same side of 0, only adjust one of them
        if ($this->max_value * $this->min_value >= 0) {
            $count = $this->max_value - $this->min_value;
            // $round_up means bars, so add space for the bar
            if ($round_up) {
                ++$count;
            }
            if (abs($this->max_value) >= abs($this->min_value)) {
                $this->max_value = $this->min_value +
                                   $this->step * ceil($count / $this->step);
            } else {
                $this->min_value = $this->max_value -
                                   $this->step * ceil($count / $this->step);
            }
        } else {
            $this->max_value = $this->step * ceil($this->max_value / $this->step);
            $this->min_value = $this->step * floor($this->min_value / $this->step);
        }

        $count = ($this->max_value - $this->min_value) / $this->step;
        $ulen  = $this->max_value - $this->min_value;
        if ($ulen == 0) {
            throw new \Exception('Zero length axis');
        }
        $this->unit_size = $this->length / $ulen;
        $grid            = $this->length / $count;
        $this->zero      = (-$this->min_value / $this->step) * $grid;

        return $grid;
    }
}
