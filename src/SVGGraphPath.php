<?php

namespace GGS\SVGGraph;

class SVGGraphPath extends SVGGraphShape
{
    protected $element = 'path';
    protected $required = array('d');
}
