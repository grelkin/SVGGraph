<?php

namespace GGS\SVGGraph;

class SVGGraphColours implements \Countable
{
    private $colours = array();
    private $dataset_count = 0;
    private $fallback = false;

    public function __construct($colours = -1)
    {
        $default_colours = array(
            '#11c',
            '#c11',
            '#cc1',
            '#1c1',
            '#c81',
            '#116',
            '#611',
            '#661',
            '#161',
            '#631',
        );

        // default colours
        if (is_null($colours)) {
            $colours = $default_colours;
        }

        if (is_array($colours)) {
            // fallback to old behaviour
            $this->fallback = $colours;

            return;
        }

        $this->colours[0]    = new SVGGraphColourArray($default_colours);
        $this->dataset_count = 1;

        return;
    }

    /**
     * Setup based on graph requirements.
     */
    public function Setup($count, $datasets = null)
    {
        if ($this->fallback !== false) {
            if (!is_null($datasets)) {
                foreach ($this->fallback as $colour) {
                    // in fallback, each dataset gets one colour
                    $this->colours[] = new SVGGraphColourArray(array($colour));
                }
            } else {
                $this->colours[] = new SVGGraphColourArray($this->fallback);
            }
            $this->dataset_count = count($this->colours);
        }

        foreach ($this->colours as $clist) {
            $clist->Setup($count);
        }
    }

    /**
     * Returns the colour for an index and dataset.
     */
    public function GetColour($index, $dataset = null)
    {
        // default is for a colour per dataset
        if (is_null($dataset)) {
            $dataset = 0;
        }

        // see if specific dataset exists
        if (array_key_exists($dataset, $this->colours)) {
            return $this->colours[$dataset][$index];
        }

        // try mod
        $dataset = $dataset % $this->dataset_count;
        if (array_key_exists($dataset, $this->colours)) {
            return $this->colours[$dataset][$index];
        }

        // just use first dataset
        reset($this->colours);
        $clist = current($this->colours);

        return $clist[$index];
    }

    /**
     * Implement Countable to make it non-countable.
     */
    public function count()
    {
        throw new \Exception('Cannot count SVGGraphColours class');
    }

    /**
     * Assign a colour array for a dataset.
     */
    public function Set($dataset, $colours)
    {
        if (is_null($colours)) {
            if (array_key_exists($dataset, $this->colours)) {
                unset($this->colours[$dataset]);
            }

            return;
        }
        $this->colours[$dataset] = new SVGGraphColourArray($colours);
        $this->dataset_count     = count($this->colours);
    }

    /**
     * Set up RGB colour range.
     */
    public function RangeRGB($dataset, $r1, $g1, $b1, $r2, $g2, $b2)
    {
        $rng                     = new SVGGraphColourRangeRGB($r1, $g1, $b1, $r2, $g2, $b2);
        $this->colours[$dataset] = $rng;
        $this->dataset_count     = count($this->colours);
    }

    /**
     * HSL colour range, with option to go the long way.
     */
    public function RangeHSL(
        $dataset,
        $h1,
        $s1,
        $l1,
        $h2,
        $s2,
        $l2,
        $reverse = false
    ) {
        $rng = new SVGGraphColourRangeHSL($h1, $s1, $l1, $h2, $s2, $l2);
        if ($reverse) {
            $rng->Reverse();
        }
        $this->colours[$dataset] = $rng;
        $this->dataset_count     = count($this->colours);
    }

    /**
     * HSL colour range from RGB values, with option to go the long way.
     */
    public function RangeRGBtoHSL(
        $dataset,
        $r1,
        $g1,
        $b1,
        $r2,
        $g2,
        $b2,
        $reverse = false
    ) {
        $rng = SVGGraphColourRangeHSL::FromRGB($r1, $g1, $b1, $r2, $g2, $b2);
        if ($reverse) {
            $rng->Reverse();
        }
        $this->colours[$dataset] = $rng;
        $this->dataset_count     = count($this->colours);
    }

    /**
     * RGB colour range from two RGB hex codes.
     */
    public function RangeHexRGB($dataset, $c1, $c2)
    {
        list($r1, $g1, $b1) = $this->HexRGB($c1);
        list($r2, $g2, $b2) = $this->HexRGB($c2);
        $this->RangeRGB($dataset, $r1, $g1, $b1, $r2, $g2, $b2);
    }

    /**
     * HSL colour range from RGB hex codes.
     */
    public function RangeHexHSL($dataset, $c1, $c2, $reverse = false)
    {
        list($r1, $g1, $b1) = $this->HexRGB($c1);
        list($r2, $g2, $b2) = $this->HexRGB($c2);
        $this->RangeRGBtoHSL($dataset, $r1, $g1, $b1, $r2, $g2, $b2, $reverse);
    }

    /**
     * Convert a colour code to RGB array.
     */
    public static function HexRGB($c)
    {
        $r = $g = $b = 0;
        if (strlen($c) == 7) {
            sscanf($c, '#%2x%2x%2x', $r, $g, $b);
        } elseif (strlen($c) == 4) {
            sscanf($c, '#%1x%1x%1x', $r, $g, $b);
            $r += 16 * $r;
            $g += 16 * $g;
            $b += 16 * $b;
        }

        return array($r, $g, $b);
    }
}
