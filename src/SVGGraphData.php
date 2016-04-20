<?php

namespace GGS\SVGGraph;

/**
 * Class for standard data.
 */
class SVGGraphData implements \Countable, \ArrayAccess, \Iterator
{
    private $datasets = 0;
    private $data;
    private $assoc = null;
    private $iterators = array();
    private $min_value = array();
    private $max_value = array();
    private $min_key = array();
    private $max_key = array();
    public $error = null;

    public function __construct(&$data, $force_assoc)
    {
        if (empty($data[0])) {
            $this->error = 'No data';

            return;
        }
        $this->data     = $data;
        $this->datasets = count($data);
        if ($force_assoc) {
            $this->assoc = true;
        }
        for ($i = 0; $i < $this->datasets; ++$i) {
            $this->iterators[$i] = new SVGGraphDataIterator($this->data, $i);
        }
    }

    /**
     * Implement Iterator interface to prevent iteration...
     */
    private function notIterator()
    {
        throw new \Exception('Cannot iterate ' . __CLASS__);
    }

    public function current()
    {
        $this->notIterator();
    }

    public function key()
    {
        $this->notIterator();
    }

    public function next()
    {
        $this->notIterator();
    }

    public function rewind()
    {
        $this->notIterator();
    }

    public function valid()
    {
        $this->notIterator();
    }

    /**
     * ArrayAccess methods.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->iterators[$offset];
    }

    /**
     * Don't allow writing to the data.
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception('Read-only');
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('Read-only');
    }

    /**
     * Countable method.
     */
    public function count()
    {
        return $this->datasets;
    }

    /**
     * Returns minimum data value for a dataset.
     */
    public function GetMinValue($dataset = 0)
    {
        if (!isset($this->min_value[$dataset])) {
            if (count($this->data[$dataset])) {
                $this->min_value[$dataset] = Graph::min($this->data[$dataset]);
            } else {
                $this->min_value[$dataset] = null;
            }
        }

        return $this->min_value[$dataset];
    }

    /**
     * Returns maximum data value for a dataset.
     */
    public function GetMaxValue($dataset = 0)
    {
        if (!isset($this->max_value[$dataset])) {
            if (count($this->data[$dataset])) {
                $this->max_value[$dataset] = max($this->data[$dataset]);
            } else {
                $this->max_value[$dataset] = null;
            }
        }

        return $this->max_value[$dataset];
    }

    /**
     * Returns the minimum key value.
     */
    public function GetMinKey($dataset = 0)
    {
        if (!isset($this->min_key[$dataset])) {
            if (count($this->data[$dataset])) {
                $this->min_key[$dataset] = $this->AssociativeKeys()
                    ? 0
                    :
                    min(array_keys($this->data[$dataset]));
            } else {
                $this->min_key[$dataset] = null;
            }
        }

        return $this->min_key[$dataset];
    }

    /**
     * Returns the maximum key value.
     */
    public function GetMaxKey($dataset = 0)
    {
        if (!isset($this->max_key[$dataset])) {
            if (count($this->data[$dataset])) {
                $this->max_key[$dataset] = $this->AssociativeKeys()
                    ?
                    count($this->data[$dataset]) - 1
                    :
                    max(array_keys($this->data[$dataset]));
            } else {
                $this->max_key[$dataset] = null;
            }
        }

        return $this->max_key[$dataset];
    }

    /**
     * Returns the key at a given index.
     */
    public function GetKey($index, $dataset = 0)
    {
        if (!$this->AssociativeKeys()) {
            return $index;
        }

        // round index to nearest integer, or PHP will floor() it
        $index = (int)round($index);
        if ($index >= 0) {
            $slice = array_slice($this->data[$dataset], $index, 1, true);
            // use foreach to get key and value
            foreach ($slice as $k => $v) {
                return $k;
            }
        }

        return;
    }

    /**
     * Returns TRUE if the keys are associative.
     */
    public function AssociativeKeys()
    {
        if (!is_null($this->assoc)) {
            return $this->assoc;
        }

        foreach (array_keys($this->data[0]) as $k) {
            if (!is_integer($k)) {
                return ($this->assoc = true);
            }
        }

        return ($this->assoc = false);
    }

    /**
     * Returns the number of data items.
     */
    public function ItemsCount($dataset = 0)
    {
        if ($dataset < 0) {
            $dataset = 0;
        }

        return count($this->data[$dataset]);
    }

    /**
     * Returns the min and max sum values.
     */
    public function GetMinMaxSumValues($start = 0, $end = null)
    {
        if ($start != 0 || (!is_null($end) && $end != 0)) {
            throw new \Exception('Dataset not found');
        }

        // structured data is used for multi-data, so just
        // return the min and max
        return array($this->GetMinValue(), $this->GetMaxValue());
    }
}
