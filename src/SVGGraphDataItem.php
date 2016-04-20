<?php

namespace GGS\SVGGraph;

/**
 * Class for single data items.
 */
class SVGGraphDataItem
{
    public $key;
    public $value;

    public function __construct($key, $value)
    {
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * Returns NULL because standard data doesn't support extra fields.
     */
    public function Data($field)
    {
        return;
    }
}
