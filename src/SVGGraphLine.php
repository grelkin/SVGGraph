<?php

namespace GGS\SVGGraph;

class SVGGraphLine extends SVGGraphShape
{
    protected $element = 'line';
    protected $required = array('x1', 'y1', 'x2', 'y2');
    protected $transform_pairs = array(array('x1', 'y1'), array('x2', 'y2'));
}
