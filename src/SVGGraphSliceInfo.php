<?php

namespace GGS\SVGGraph;

/**
 * Class for details of each pie slice.
 */
class SVGGraphSliceInfo
{
    public $start_angle;
    public $end_angle;
    public $radius_x;
    public $radius_y;

    public function __construct($start, $end, $rx, $ry)
    {
        $this->start_angle = $start;
        $this->end_angle   = $end;
        $this->radius_x    = $rx;
        $this->radius_y    = $ry;
    }

    /*
     * Calculates the middle angle of the slice
     */
    public function MidAngle()
    {
        return $this->start_angle + ($this->end_angle - $this->start_angle) / 2;
    }

    /**
     * Returns the slice angle in degrees.
     */
    public function Degrees()
    {
        return rad2deg($this->end_angle - $this->start_angle);
    }
}
