<?php

namespace GGS\SVGGraph;

/**
 * Class to iterate over standard data.
 */
class SVGGraphDataIterator implements \Iterator
{
    private $data = 0;
    private $dataset = 0;
    private $position = 0;
    private $count = 0;

    public function __construct(&$data, $dataset)
    {
        $this->dataset = $dataset;
        $this->data    = &$data;
        $this->count   = count($data[$dataset]);
    }

    /**
     * Iterator methods.
     */
    public function current()
    {
        return $this->GetItemByIndex($this->position);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
        next($this->data[$this->dataset]);
    }

    public function rewind()
    {
        $this->position = 0;
        reset($this->data[$this->dataset]);
    }

    public function valid()
    {
        return $this->position < $this->count;
    }

    /**
     * Returns an item by index.
     *
     * @param $index
     *
     * @return SVGGraphDataItem|void
     */
    public function GetItemByIndex($index)
    {
        $slice = array_slice($this->data[$this->dataset], $index, 1, true);
        // use foreach to get key and value
        foreach ($slice as $k => $v) {
            return new SVGGraphDataItem($k, $v);
        }

        return;
    }

    /**
     * Returns an item by its key.
     * @param $key
     * @return SVGGraphDataItem|void
*/
    public function GetItemByKey($key)
    {
        if (isset($this->data[$this->dataset][$key])) {
            return new SVGGraphDataItem($key, $this->data[$this->dataset][$key]);
        }

        return;
    }
}
