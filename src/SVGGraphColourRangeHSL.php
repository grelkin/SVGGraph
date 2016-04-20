<?php

namespace GGS\SVGGraph;

/**
 * Colour range for HSL values.
 */
class SVGGraphColourRangeHSL extends SVGGraphColourRange
{
    private $h1, $s1, $l1;
    private $hdiff, $sdiff, $ldiff;

    /**
     * HSL range.
     */
    public function __construct($h1, $s1, $l1, $h2, $s2, $l2)
    {
        $this->h1 = $this->Clamp($h1, 0, 360);
        $this->s1 = $this->Clamp($s1, 0, 1);
        $this->l1 = $this->Clamp($l1, 0, 1);

        $hdiff = $this->Clamp($h2, 0, 360) - $this->h1;
        if (abs($hdiff) > 180) {
            $hdiff += $hdiff < 0 ? 360 : -360;
        }
        $this->hdiff = $hdiff;
        $this->sdiff = $this->Clamp($s2, 0, 1) - $this->s1;
        $this->ldiff = $this->Clamp($l2, 0, 1) - $this->l1;
    }

    /**
     * Reverse direction of colour cycle.
     */
    public function Reverse()
    {
        $this->hdiff += $this->hdiff < 0 ? 360 : -360;
    }

    /**
     * Return the colour from the range.
     */
    public function offsetGet($offset)
    {
        $c      = max($this->count - 1, 1);
        $offset = $this->Clamp($offset, 0, $c);
        $h      = fmod(360 + $this->h1 + $offset * $this->hdiff / $c, 360);
        $s      = $this->s1 + $offset * $this->sdiff / $c;
        $l      = $this->l1 + $offset * $this->ldiff / $c;

        list($r, $g, $b) = $this->HSLtoRGB($h, $s, $l);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Factory method creates an instance from RGB values.
     */
    public static function FromRGB($r1, $g1, $b1, $r2, $g2, $b2)
    {
        list($h1, $s1, $l1) = self::RGBtoHSL($r1, $g1, $b1);
        list($h2, $s2, $l2) = self::RGBtoHSL($r2, $g2, $b2);

        return new self($h1, $s1, $l1, $h2, $s2, $l2);
    }

    /**
     * Convert RGB to HSL (0-360, 0-1, 0-1).
     */
    public static function RGBtoHSL($r, $g, $b)
    {
        $r1    = self::Clamp($r, 0, 255) / 255;
        $g1    = self::Clamp($g, 0, 255) / 255;
        $b1    = self::Clamp($b, 0, 255) / 255;
        $cmax  = max($r1, $g1, $b1);
        $cmin  = min($r1, $g1, $b1);
        $delta = $cmax - $cmin;

        $l = ($cmax + $cmin) / 2;
        if ($delta == 0) {
            $h = $s = 0;
        } else {
            if ($cmax == $r1) {
                $h = fmod(($g1 - $b1) / $delta, 6);
            } elseif ($cmax == $g1) {
                $h = 2 + ($b1 - $r1) / $delta;
            } else {
                $h = 4 + ($r1 - $g1) / $delta;
            }
            $h = fmod(360 + ($h * 60), 360);
            $s = $delta / (1 - abs(2 * $l - 1));
        }

        return array($h, $s, $l);
    }

    /**
     * Convert HSL to RGB.
     */
    public static function HSLtoRGB($h, $s, $l)
    {
        $h1 = self::Clamp($h, 0, 360);
        $s1 = self::Clamp($s, 0, 1);
        $l1 = self::Clamp($l, 0, 1);

        $c = (1 - abs(2 * $l1 - 1)) * $s1;
        $x = $c * (1 - abs(fmod($h1 / 60, 2) - 1));
        $m = $l1 - $c / 2;

        $c = 255 * ($c + $m);
        $x = 255 * ($x + $m);
        $m *= 255;
        switch (floor($h1 / 60)) {
            case 0 :
                $rgb = array($c, $x, $m);
                break;
            case 1 :
                $rgb = array($x, $c, $m);
                break;
            case 2 :
                $rgb = array($m, $c, $x);
                break;
            case 3 :
                $rgb = array($m, $x, $c);
                break;
            case 4 :
                $rgb = array($x, $m, $c);
                break;
            case 5 :
                $rgb = array($c, $m, $x);
                break;
        }

        return $rgb;
    }
}
