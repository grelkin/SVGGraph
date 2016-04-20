<?php

namespace GGS\SVGGraph;

class SVGGraphEllipse extends SVGGraphShape
{
    protected $element = 'ellipse';
    protected $required = array('cx', 'cy', 'rx', 'ry');
    protected $transform = array('rx' => 'x', 'ry' => 'y');
    protected $transform_pairs = array(array('cx', 'cy'));
}
