<?php

namespace GGS\SVGGraph;

/**
 * Colour range for RGB values.
 */
class SVGGraphColourRangeRGB extends SVGGraphColourRange
{
    private $r1, $g1, $b1;
    private $rdiff, $gdiff, $bdiff;

    /**
     * RGB range.
     */
    public function __construct($r1, $g1, $b1, $r2, $g2, $b2)
    {
        $this->r1    = $this->Clamp($r1, 0, 255);
        $this->g1    = $this->Clamp($g1, 0, 255);
        $this->b1    = $this->Clamp($b1, 0, 255);
        $this->rdiff = $this->Clamp($r2, 0, 255) - $this->r1;
        $this->gdiff = $this->Clamp($g2, 0, 255) - $this->g1;
        $this->bdiff = $this->Clamp($b2, 0, 255) - $this->b1;
    }

    /**
     * Return the colour from the range.
     */
    public function offsetGet($offset)
    {
        $c      = max($this->count - 1, 1);
        $offset = $this->Clamp($offset, 0, $c);
        $r      = $this->r1 + $offset * $this->rdiff / $c;
        $g      = $this->g1 + $offset * $this->gdiff / $c;
        $b      = $this->b1 + $offset * $this->bdiff / $c;

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
