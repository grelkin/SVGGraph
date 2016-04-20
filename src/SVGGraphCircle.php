<?php

namespace GGS\SVGGraph;

class SVGGraphCircle extends SVGGraphShape
{
    protected $element = 'circle';
    protected $required = array('cx', 'cy', 'r');
    protected $transform = array('r' => 'y');
    protected $transform_pairs = array(array('cx', 'cy'));
}
