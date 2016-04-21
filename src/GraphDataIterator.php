<?php

namespace GGS\SVGGraph;

class GraphDataIterator extends \IteratorIterator
{
    private $position = 0;

    public function __construct(array $data)
    {
        parent::__construct(new \ArrayIterator($data));
    }

    public function current()
    {
        return new SVGGraphDataItem(parent::key(), parent::current());
    }

    public function next()
    {
        parent::next();
        $this->position++;
    }

    public function rewind()
    {
        parent::rewind();
        $this->position = 0;
    }

    public function key()
    {
        return $this->position;
    }
}
