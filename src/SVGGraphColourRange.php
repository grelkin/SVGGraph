<?php

namespace GGS\SVGGraph;

/**
 * Abstract class implements common methods.
 */
abstract class SVGGraphColourRange implements \ArrayAccess
{
    protected $count = 2;

    /**
     * Sets up the length of the range.
     */
    public function Setup($count)
    {
        $this->count = $count;
    }

    /**
     * always true, because it wraps around.
     */
    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception('Unexpected offsetSet');
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('Unexpected offsetUnset');
    }

    /**
     * Clamps a value to range $min-$max.
     */
    protected static function Clamp($val, $min, $max)
    {
        return min($max, max($min, $val));
    }
}
