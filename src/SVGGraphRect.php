<?php

namespace GGS\SVGGraph;

class SVGGraphRect extends SVGGraphShape
{
    protected $element = 'rect';
    protected $required = array('x', 'y', 'width', 'height');
    protected $transform = array('width' => 'x', 'height' => 'y');
    protected $transform_pairs = array(array('x', 'y'));
}
