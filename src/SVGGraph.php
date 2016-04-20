<?php

namespace GGS\SVGGraph;

define('SVGGRAPH_VERSION', 'SVGGraph 2.21');

class SVGGraph
{
    private $width = 100;
    private $height = 100;
    private $settings = array();
    public $values = array();
    public $links = null;
    public $colours = null;

    public function __construct($w, $h, $settings = null)
    {
        $this->width  = $w;
        $this->height = $h;

        if (is_array($settings)) {
            // structured_data, when FALSE disables structure
            if (isset($settings['structured_data']) && !$settings['structured_data']) {
                unset($settings['structure']);
            }
            $this->settings = $settings;
        }
    }

    public function Values($values)
    {
        if (is_array($values)) {
            $this->values = $values;
        } else {
            $this->values = func_get_args();
        }
    }

    public function Links($links)
    {
        if (is_array($links)) {
            $this->links = $links;
        } else {
            $this->links = func_get_args();
        }
    }

    /**
     * Assign a single colour set for use across datasets.
     *
     * @param $colours
     */
    public function Colours($colours)
    {
        $this->colours = $colours;
    }

    /**
     * Sets colours for a single dataset.
     * @param $dataset
     * @param $colours
     */
    public function ColourSet($dataset, $colours)
    {
        if (!is_object($this->colours)) {
            $this->colours = new SVGGraphColours();
        }
        $this->colours->Set($dataset, $colours);
    }

    /**
     * Sets up RGB colour range.
     * @param $dataset
     * @param $r1
     * @param $g1
     * @param $b1
     * @param $r2
     * @param $g2
     * @param $b2
     */
    public function ColourRangeRGB($dataset, $r1, $g1, $b1, $r2, $g2, $b2)
    {
        if (!is_object($this->colours)) {
            $this->colours = new SVGGraphColours();
        }
        $this->colours->RangeRGB($dataset, $r1, $g1, $b1, $r2, $g2, $b2);
    }

    /**
     * RGB colour range from hex codes.
     * @param $dataset
     * @param $c1
     * @param $c2
     */
    public function ColourRangeHexRGB($dataset, $c1, $c2)
    {
        if (!is_object($this->colours)) {
            $this->colours = new SVGGraphColours();
        }
        $this->colours->RangeHexRGB($dataset, $c1, $c2);
    }

    /**
     * Sets up HSL colour range.
     * @param      $dataset
     * @param      $h1
     * @param      $s1
     * @param      $l1
     * @param      $h2
     * @param      $s2
     * @param      $l2
     * @param bool $reverse
     */
    public function ColourRangeHSL(
        $dataset,
        $h1,
        $s1,
        $l1,
        $h2,
        $s2,
        $l2,
        $reverse = false
    ) {
        if (!is_object($this->colours)) {
            $this->colours = new SVGGraphColours();
        }
        $this->colours->RangeHSL($dataset, $h1, $s1, $l1, $h2, $s2, $l2, $reverse);
    }

    /**
     * HSL colour range from hex codes.
     * @param      $dataset
     * @param      $c1
     * @param      $c2
     * @param bool $reverse
     */
    public function ColourRangeHexHSL($dataset, $c1, $c2, $reverse = false)
    {
        if (!is_object($this->colours)) {
            $this->colours = new SVGGraphColours();
        }
        $this->colours->RangeHexHSL($dataset, $c1, $c2, $reverse);
    }

    /**
     * Sets up HSL colour range from RGB values.
     * @param      $dataset
     * @param      $r1
     * @param      $g1
     * @param      $b1
     * @param      $r2
     * @param      $g2
     * @param      $b2
     * @param bool $reverse
     */
    public function ColourRangeRGBtoHSL(
        $dataset,
        $r1,
        $g1,
        $b1,
        $r2,
        $g2,
        $b2,
        $reverse = false
    ) {
        if (!is_object($this->colours)) {
            $this->colours = new SVGGraphColours();
        }
        $this->colours->RangeRGBtoHSL(
            $dataset,
            $r1,
            $g1,
            $b1,
            $r2,
            $g2,
            $b2,
            $reverse
        );
    }

    /**
     * Instantiate the correct class.
     * @param $class
     * @return
     */
    private function Setup($class)
    {
        $class_name = 'GGS\\SVGGraph\\' . $class;
        $g          = new $class_name($this->width, $this->height, $this->settings);
        $g->Values($this->values);
        $g->Links($this->links);
        if (is_object($this->colours)) {
            $g->colours = $this->colours;
        } else {
            $g->colours = new SVGGraphColours($this->colours);
        }

        return $g;
    }

    /**
     * Fetch the content.
     * @param      $class
     * @param bool $header
     * @param bool $defer_js
     * @return
     */
    public function Fetch($class, $header = true, $defer_js = true)
    {
        $this->g = $this->Setup($class);

        return $this->g->Fetch($header, $defer_js);
    }

    /**
     * Pass in the type of graph to display.
     * @param      $class
     * @param bool $header
     * @param bool $content_type
     * @param bool $defer_js
     * @return
     */
    public function Render(
        $class,
        $header = true,
        $content_type = true,
        $defer_js = false
    ) {
        $this->g = $this->Setup($class);

        return $this->g->Render($header, $content_type, $defer_js);
    }

    public function FetchJavascript()
    {
        if (isset($this->g)) {
            return $this->g->FetchJavascript(true, true, true);
        }

        return '';
    }
}
