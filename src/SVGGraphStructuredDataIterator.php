<?php

namespace GGS\SVGGraph;

/**
 * For iterating over structured data.
 */
class SVGGraphStructuredDataIterator implements \Iterator
{
    private $data = 0;
    private $dataset = 0;
    private $position = 0;
    private $count = 0;
    private $structure = null;
    private $key_field = 0;
    private $dataset_fields = array();

    public function __construct(&$data, $dataset, $structure)
    {
        $this->dataset   = $dataset;
        $this->data      = &$data;
        $this->count     = count($data);
        $this->structure = $structure;

        $this->key_field      = $structure['key'];
        $this->dataset_fields = $structure['value'];
    }

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
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return $this->position < $this->count;
    }

    /**
     * Returns an item by index.
     */
    public function GetItemByIndex($index)
    {
        if (isset($this->data[$index])) {
            $item = $this->data[$index];
            $key  = is_null($this->key_field) ? $index : null;

            return new SVGGraphStructuredDataItem(
                $this->data[$index],
                $this->structure, $this->dataset, $key
            );
        }

        return;
    }

    /**
     * Returns an item by key.
     */
    public function GetItemByKey($key)
    {
        if (is_null($this->key_field)) {
            if (isset($this->data[$key])) {
                return new SVGGraphStructuredDataItem(
                    $this->data[$key],
                    $this->structure, $this->dataset, $key
                );
            }
        } else {
            foreach ($this->data as $item) {
                if (isset($item[$this->key_field]) && $item[$this->key_field] == $key) {
                    return new SVGGraphStructuredDataItem(
                        $item, $this->structure,
                        $this->dataset, $key
                    );
                }
            }
        }

        return;
    }
}
