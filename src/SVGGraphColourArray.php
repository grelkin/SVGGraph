<?php

namespace GGS\SVGGraph;

class SVGGraphColourArray implements \ArrayAccess
{
    private $colours;
    private $count;

    public function __construct($colours)
    {
        $this->colours = $colours;
        $this->count   = count($colours);
    }

    /**
     * Not used by this class.
     *
     * @param $count
     */
    public function Setup($count)
    {
        // count comes from array, not number of bars etc.
    }

    /**
     * always true, because it wraps around.
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     * return the colour.
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->colours[$offset % $this->count];
    }

    public function offsetSet($offset, $value)
    {
        $this->colours[$offset % $this->count] = $value;
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('Unexpected offsetUnset');
    }
}
