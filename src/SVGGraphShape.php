<?php

namespace GGS\SVGGraph;

define('SVGG_SHAPE_ABOVE', 1);
define('SVGG_SHAPE_BELOW', 0);

abstract class SVGGraphShape
{
    protected $depth = SVGG_SHAPE_BELOW;
    protected $element = '';
    protected $link = null;
    protected $link_target = '_blank';
    protected $coords = null;

    /**
     * attributes required to draw shape.
     */
    protected $required = array();

    /**
     * attributes that support coordinate transformation.
     */
    protected $transform = array();

    /**
     * coordinate pairs for dependent transforns - don't include them in
     * $transform or they will be transformed twice.
     */
    protected $transform_pairs = array();

    /**
     * colour gradients/patterns, and whether to allow gradients.
     */
    private $colour_convert = array(
        'stroke' => true,
        'fill'   => false,
    );

    /**
     * default attributes for all shapes.
     */
    protected $attrs = array(
        'stroke' => '#000',
        'fill'   => 'none',
    );

    public function __construct(&$attrs, $depth)
    {
        $this->attrs = array_merge($this->attrs, $attrs);
        $this->depth = $depth;

        $missing = array();
        foreach ($this->required as $opt) {
            if (!isset($this->attrs[$opt])) {
                $missing[] = $opt;
            }
        }

        if (count($missing)) {
            throw new \Exception(
                "{$this->element} attribute(s) not found: " .
                implode(', ', $missing)
            );
        }

        if (isset($this->attrs['href'])) {
            $this->link = $this->attrs['href'];
        }
        if (isset($this->attrs['xlink:href'])) {
            $this->link = $this->attrs['xlink:href'];
        }
        if (isset($this->attrs['target'])) {
            $this->link_target = $this->attrs['target'];
        }
        unset($this->attrs['href'], $this->attrs['xlink:href'],
            $this->attrs['target']);
    }

    /**
     * returns true if the depth is correct.
     */
    public function Depth($d)
    {
        return $this->depth == $d;
    }

    /**
     * draws the shape.
     */
    public function Draw(&$graph)
    {
        $this->coords = new SVGGraphCoords($graph);

        $attributes = array();
        foreach ($this->attrs as $attr => $value) {
            if (!is_null($value)) {
                if (isset($this->transform[$attr])) {
                    $val = $this->coords->Transform($value, $this->transform[$attr]);
                } else {
                    $val = isset($this->colour_convert[$attr])
                        ?
                        $graph->ParseColour($value, null, $this->colour_convert[$attr])
                        :
                        $value;
                }
                $attr              = str_replace('_', '-', $attr);
                $attributes[$attr] = $val;
            }
        }
        $this->TransformCoordinates($attributes);
        $element = $this->DrawElement($graph, $attributes);
        if (!is_null($this->link)) {
            $link = array('xlink:href' => $this->link);
            if (!is_null($this->link_target)) {
                $link['target'] = $this->link_target;
            }
            $element = $graph->Element('a', $link, null, $element);
        }

        return $element;
    }

    /**
     * Transform coordinate pairs.
     */
    protected function TransformCoordinates(&$attributes)
    {
        if (count($this->transform_pairs)) {
            foreach ($this->transform_pairs as $pair) {
                $coords               = $this->coords->TransformCoords(
                    $attributes[$pair[0]],
                    $attributes[$pair[1]]
                );
                $attributes[$pair[0]] = $coords[0];
                $attributes[$pair[1]] = $coords[1];
            }
        }
    }

    /**
     * Performs the conversion to SVG fragment.
     */
    protected function DrawElement(&$graph, &$attributes)
    {
        return $graph->Element($this->element, $attributes);
    }

    /**
     * splits $value, removing leading char and updating $axis.
     */
    private function ValueAxis(&$value, &$axis)
    {
        // strip leading u or g
        $value = substr($value, 1);
        $last  = substr($value, -1);
        if ($last == 'x' || $last == 'y') {
            // axis given, strip last char
            $axis  = $last;
            $value = substr($value, 0, -1);
        }
    }
}
