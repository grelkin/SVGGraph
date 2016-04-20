<?php

namespace GGS\SVGGraph;

/**
 * Arbitrary shapes for adding to graphs.
 */
class SVGGraphShapeList
{
    private $graph;
    private $shapes = array();

    public function __construct(&$graph)
    {
        $this->graph = $graph;
    }

    /**
     * Load shapes from options list.
     */
    public function Load(&$settings)
    {
        if (!isset($settings['shape'])) {
            return;
        }

        if (!is_array($settings['shape']) || !isset($settings['shape'][0])) {
            throw new \Exception('Malformed shape option');
        }

        if (!is_array($settings['shape'][0])) {
            $this->AddShape($settings['shape']);
        } else {
            foreach ($settings['shape'] as $shape) {
                $this->AddShape($shape);
            }
        }
    }

    /**
     * Draw all the shapes for the selected depth.
     */
    public function Draw($depth)
    {
        $content = array();
        foreach ($this->shapes as $shape) {
            if ($shape->Depth($depth)) {
                $content[] = $shape->Draw($this->graph);
            }
        }

        return implode($content);
    }

    /**
     * Adds a shape from config array.
     */
    private function AddShape(&$shape_array)
    {
        $shape = $shape_array[0];
        unset($shape_array[0]);

        $class_map = array(
            'circle'   => 'SVGGraphCircle',
            'ellipse'  => 'SVGGraphEllipse',
            'rect'     => 'SVGGraphRect',
            'line'     => 'SVGGraphLine',
            'polyline' => 'SVGGraphPolyLine',
            'polygon'  => 'SVGGraphPolygon',
            'path'     => 'SVGGraphPath',
        );

        if (isset($class_map[$shape]) && class_exists($class_map[$shape])) {
            $depth = SVGG_SHAPE_BELOW;
            if (isset($shape_array['depth'])) {
                if ($shape_array['depth'] == 'above') {
                    $depth = SVGG_SHAPE_ABOVE;
                }
            }
            if (isset($shape_array['clip_to_grid']) && $shape_array['clip_to_grid'] &&
                method_exists($this->graph, 'GridClipPath')
            ) {
                $clip_id                  = $this->graph->GridClipPath();
                $shape_array['clip-path'] = "url(#{$clip_id})";
            }
            unset($shape_array['depth'], $shape_array['clip_to_grid']);
            $this->shapes[] = new $class_map[$shape]($shape_array, $depth);
        } else {
            throw new \Exception("Unknown shape [{$shape}]");
        }
    }
}
