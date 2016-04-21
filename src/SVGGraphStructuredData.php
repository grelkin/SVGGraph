<?php

namespace GGS\SVGGraph;

/**
 * Class for structured data.
 */
class SVGGraphStructuredData implements \Countable, \ArrayAccess, \Iterator
{
    private $datasets = 0;
    private $key_field = 0;
    private $axis_text_field = null;
    private $dataset_fields = array();
    private $data;
    private $force_assoc = false;
    private $assoc = null;
    private $repeated_keys;
    private $assoc_test;
    private $structure = array();
    private $iterators = array();
    private $max_keys = array();
    private $min_keys = array();
    private $max_values = array();
    private $min_values = array();
    private $before_label = '';
    private $after_label = '';
    private $encoding = 'UTF-8';
    public $error = null;

    public function __construct(
        &$data,
        $force_assoc,
        $structure,
        $repeated_keys,
        $integer_keys,
        $requirements
    ) {
        if (!is_null($structure) && !empty($structure)) {
            // structure provided, is it valid?
            foreach (array('key', 'value') as $field) {
                if (!array_key_exists($field, $structure)) {
                    $this->error = $field . ' field not set for structured data';

                    return;
                }
            }

            if (!is_array($structure['value'])) {
                $structure['value'] = array($structure['value']);
            }
            $this->key_field      = $structure['key'];
            $this->dataset_fields = is_array($structure['value']) ?
                $structure['value'] : array($structure['value']);
            if (isset($structure['_before'])) {
                $this->before_label = $structure['_before'];
            }
            if (isset($structure['_after'])) {
                $this->after_label = $structure['_after'];
            }
            if (isset($structure['_encoding'])) {
                $this->encoding = $structure['_encoding'];
            }
        } else {
            // find key and datasets
            $keys                 = array_keys($data[0]);
            $this->key_field      = array_shift($keys);
            $this->dataset_fields = $keys;

            // check for more datasets
            foreach ($data as $item) {
                foreach (array_keys($item) as $key) {
                    if ($key != $this->key_field &&
                        array_search($key, $this->dataset_fields) === false
                    ) {
                        $this->dataset_fields[] = $key;
                    }
                }
            }
            sort($this->dataset_fields, SORT_NUMERIC);

            // default structure
            $structure = array(
                'key'   => $this->key_field,
                'value' => $this->dataset_fields,
            );
        }

        // check any extra requirements
        if (is_array($requirements)) {
            $missing = array();
            foreach ($requirements as $req) {
                if (!isset($structure[$req])) {
                    $missing[] = $req;
                }
            }
            if (!empty($missing)) {
                $missing     = implode(', ', $missing);
                $this->error = "Required field(s) [{$missing}] not set in data structure";

                return;
            }
        }

        $this->structure = $structure;
        // check if it really has more than one dataset
        if (isset($structure['datasets']) && $structure['datasets'] &&
            is_array(current($data)) && is_array(current(current($data)))
        ) {
            $this->Scatter2DDatasets($data);
        } else {
            $this->data = &$data;
        }
        $this->datasets    = count($this->dataset_fields);
        $this->force_assoc = $force_assoc;
        $this->assoc_test  = $integer_keys ? 'is_int' : 'is_numeric';
        if (isset($structure['axis_text'])) {
            $this->axis_text_field = $structure['axis_text'];
        }

        if ($this->AssociativeKeys()) {
            // reindex the array to 0, 1, 2, ...
            $this->data = array_values($this->data);
        } elseif (!is_null($this->key_field)) {
            // if not associative, sort by key field
            $key_field = $this->key_field;
            usort($this->data, function($a, $b) use ($key_field)
            {
                if (!isset($a[$key_field]) || !isset($b[$key_field])) {
                    return 0;
                }
                if ($a[$key_field] == $b[$key_field]) {
                    return 0;
                }

                return $a[$key_field] > $b[$key_field] ? 1 : -1;
            });
        }

        if ($this->RepeatedKeys()) {
            if ($repeated_keys == 'force_assoc') {
                $this->force_assoc = true;
            } elseif ($repeated_keys != 'accept') {
                $this->error = 'Repeated keys in data';
            }
        }
        if (!$this->error) {
            for ($i = 0; $i < $this->datasets; ++$i) {
                $this->iterators[$i] = new SVGGraphStructuredDataIterator(
                    $this->data,
                    $i,
                    $this->structure
                );
            }
        }
    }

    /**
     * Sets up normal structured data from scatter_2d datasets.
     *
     * @param $data
     */
    private function Scatter2DDatasets(&$data)
    {
        $newdata     = array();
        $key_field   = $this->structure['key'];
        $value_field = $this->structure['value'][0];

        // update structure
        $this->structure['key']   = 0;
        $this->structure['value'] = array();
        $this->key_field          = 0;
        $this->dataset_fields     = array();
        $set                      = 1;
        foreach ($data as $dataset) {
            foreach ($dataset as $item) {
                if (isset($item[$key_field]) && isset($item[$value_field])) {
                    // no need to dedupe keys - no extra data and scatter_2d
                    // only supported by scatter graphs
                    $newdata[] = array(0 => $item[$key_field], $set => $item[$value_field]);
                }
            }
            $this->structure['value'][] = $set;
            $this->dataset_fields[]     = $set;
            ++$set;
        }
        $this->data = $newdata;
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
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->dataset_fields);
    }

    public function offsetGet($offset)
    {
        return $this->iterators[$offset];
    }

    /**
     * Don't allow writing to the data.
     * @param mixed $offset
     * @param mixed $value
     * @throws \Exception
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
     * @param int $dataset
     * @return null
     */
    public function GetMinValue($dataset = 0)
    {
        if (isset($this->min_values[$dataset])) {
            return $this->min_values[$dataset];
        }

        $min = null;
        $key = $this->dataset_fields[$dataset];
        foreach ($this->data as $item) {
            if (isset($item[$key]) && (is_null($min) || $item[$key] < $min)) {
                $min = $item[$key];
            }
        }

        return ($this->min_values[$dataset] = $min);
    }

    /**
     * Returns maximum data value for a dataset.
     * @param int $dataset
     * @return null
     */
    public function GetMaxValue($dataset = 0)
    {
        if (isset($this->max_values[$dataset])) {
            return $this->max_values[$dataset];
        }

        $max = null;
        $key = $this->dataset_fields[$dataset];
        foreach ($this->data as $item) {
            if (isset($item[$key]) && (is_null($max) || $item[$key] > $max)) {
                $max = $item[$key];
            }
        }

        return ($this->max_values[$dataset] = $max);
    }

    /**
     * Returns the minimum key value.
     * @param int $dataset
     * @return int|null|string
     */
    public function GetMinKey($dataset = 0)
    {
        if (isset($this->min_keys[$dataset])) {
            return $this->min_keys[$dataset];
        }

        if ($this->AssociativeKeys()) {
            return ($this->min_keys[$dataset] = 0);
        }

        $min = null;
        $key = $this->key_field;
        $set = $this->dataset_fields[$dataset];
        if (is_null($key)) {
            foreach ($this->data as $k => $item) {
                if (isset($item[$set]) && (is_null($min) || $k < $min)) {
                    $min = $k;
                }
            }
        } else {
            foreach ($this->data as $item) {
                if (isset($item[$key]) && isset($item[$set]) &&
                    (is_null($min) || $item[$key] < $min)
                ) {
                    $min = $item[$key];
                }
            }
        }

        return ($this->min_keys[$dataset] = $min);
    }

    /**
     * Returns the maximum key value for a dataset.
     * @param int $dataset
     * @return int|null|string
     */
    public function GetMaxKey($dataset = 0)
    {
        if (isset($this->max_keys[$dataset])) {
            return $this->max_keys[$dataset];
        }

        if ($this->AssociativeKeys()) {
            return ($this->max_keys[$dataset] = count($this->data) - 1);
        }

        $max = null;
        $key = $this->key_field;
        $set = $this->dataset_fields[$dataset];
        if (is_null($key)) {
            foreach ($this->data as $k => $item) {
                if (isset($item[$set]) && (is_null($max) || $k > $max)) {
                    $max = $k;
                }
            }
        } else {
            foreach ($this->data as $item) {
                if (isset($item[$key]) && isset($item[$set]) &&
                    (is_null($max) || $item[$key] > $max)
                ) {
                    $max = $item[$key];
                }
            }
        }

        return ($this->max_keys[$dataset] = $max);
    }

    /**
     * Returns the key (or axis text) at a given index/key.
     * @param     $index
     * @param int $dataset
     * @return int|void
     */
    public function GetKey($index, $dataset = 0)
    {
        if (is_null($this->axis_text_field) && !$this->AssociativeKeys()) {
            return $index;
        }
        $index = (int)round($index);
        if ($this->AssociativeKeys()) {
            $item = $this->iterators[$dataset]->GetItemByIndex($index);
        } else {
            $item = $this->iterators[$dataset]->GetItemByKey($index);
        }
        if (is_null($item)) {
            return;
        }
        if ($this->axis_text_field) {
            return $item->RawData($this->axis_text_field);
        }

        return $item->key;
    }

    /**
     * Returns TRUE if the keys are associative.
     */
    public function AssociativeKeys()
    {
        if ($this->force_assoc) {
            return true;
        }

        if (!is_null($this->assoc)) {
            return $this->assoc;
        }

        // use either is_int or is_numeric to test
        $test = $this->assoc_test;
        if (is_null($this->key_field)) {
            foreach ($this->data as $k => $item) {
                if (!$test($k)) {
                    return ($this->assoc = true);
                }
            }
        } else {
            foreach ($this->data as $item) {
                if (isset($item[$this->key_field]) && !$test($item[$this->key_field])) {
                    return ($this->assoc = true);
                }
            }
        }

        return ($this->assoc = false);
    }

    /**
     * Returns the number of data items in a dataset
     * If $dataset is -1, returns number of items across all datasets.
     * @param int $dataset
     * @return int
     */
    public function ItemsCount($dataset = 0)
    {
        if ($dataset == -1) {
            return count($this->data);
        }

        if (!isset($this->dataset_fields[$dataset])) {
            return 0;
        }
        $count = 0;
        $key   = $this->dataset_fields[$dataset];
        foreach ($this->data as $item) {
            if (isset($item[$key])) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Returns TRUE if there are repeated keys
     * (also culls items without key field).
     */
    public function RepeatedKeys()
    {
        if (!is_null($this->repeated_keys)) {
            return $this->repeated_keys;
        }
        if (is_null($this->key_field)) {
            return false;
        }
        $keys = array();
        foreach ($this->data as $k => $item) {
            if (!isset($item[$this->key_field])) {
                unset($this->data[$k]);
            } else {
                $keys[] = $item[$this->key_field];
            }
        }
        $len   = count($keys);
        $ukeys = array_unique($keys);

        return ($this->repeated_keys = ($len != count($ukeys)));
    }

    /**
     * Returns the min and max sum values for some datasets.
     * @param int  $start
     * @param null $end
     * @return array
     * @throws \Exception
     */
    public function GetMinMaxSumValues($start = 0, $end = null)
    {
        if ($start >= $this->datasets || (!is_null($end) && $end >= $this->datasets)) {
            throw new \Exception('Dataset not found');
        }

        if (is_null($end)) {
            $end = $this->datasets - 1;
        }
        $min_stack = array();
        $max_stack = array();

        foreach ($this->data as $item) {
            $smin = $smax = 0;
            for ($dataset = $start; $dataset <= $end; ++$dataset) {
                $vfield = $this->dataset_fields[$dataset];
                if (!isset($item[$vfield])) {
                    continue;
                }
                $value = $item[$vfield];
                if (!is_null($value) && !is_numeric($value)) {
                    throw new \Exception('Non-numeric value');
                }
                if ($value > 0) {
                    $smax += $value;
                } else {
                    $smin += $value;
                }
            }
            $min_stack[] = $smin;
            $max_stack[] = $smax;
        }
        if (!count($min_stack)) {
            return array(null, null);
        }

        return array(min($min_stack), max($max_stack));
    }

    /**
     * Strips units from before and after label.
     * @param $label
     * @return string
     */
    protected function StripLabel($label)
    {
        $before = $this->before_label;
        $after  = $this->after_label;
        $enc    = $this->encoding;
        $llen   = mb_strlen($label, $enc);
        $blen   = mb_strlen($before, $enc);
        $alen   = mb_strlen($after, $enc);
        if ($alen > 0 && mb_substr($label, $llen - $alen, $alen, $enc) == $after) {
            $label = mb_substr($label, 0, $llen - $alen, $enc);
        }
        if ($blen > 0 && mb_substr($label, 0, $blen, $enc) == $before) {
            $label = mb_substr($label, $blen, null, $enc);
        }

        return $label;
    }
}
