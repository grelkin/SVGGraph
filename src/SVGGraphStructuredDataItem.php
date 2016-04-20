<?php

namespace GGS\SVGGraph;

/**
 * Class for structured data items.
 */
class SVGGraphStructuredDataItem
{
    private $item;
    private $dataset = 0;
    private $key_field = 0;
    private $dataset_fields = array();
    private $structure;
    public $key = 0;
    public $value = null;

    public function __construct($item, &$structure, $dataset, $key = null)
    {
        $this->item           = $item;
        $this->key_field      = $structure['key'];
        $this->dataset_fields = $structure['value'];
        $this->key            = is_null($this->key_field) ? $key : $item[$this->key_field];
        if (isset($this->dataset_fields[$dataset]) &&
            isset($item[$this->dataset_fields[$dataset]])
        ) {
            $this->value = $item[$this->dataset_fields[$dataset]];
        }

        $this->dataset   = $dataset;
        $this->structure = &$structure;
    }

    /**
     * Constructs a new data item with a different dataset.
     *
     * @param $dataset
     *
     * @return SVGGraphStructuredDataItem
     */
    public function NewFrom($dataset)
    {
        return new self(
            $this->item, $this->structure,
            $dataset, $this->key
        );
    }

    /**
     * Returns some extra data from item.
     * @param $field
     * @return null|void
*/
    public function Data($field)
    {
        if (!isset($this->structure[$field])) {
            return;
        }
        $item_field = $this->structure[$field];
        if (is_array($item_field)) {
            if (!isset($item_field[$this->dataset])) {
                return;
            }
            $item_field = $item_field[$this->dataset];
        }

        return isset($this->item[$item_field]) ? $this->item[$item_field] : null;
    }

    /**
     * Returns a value from the item without translating structure.
     * @param $field
     * @return null
*/
    public function RawData($field)
    {
        return isset($this->item[$field]) ? $this->item[$field] : null;
    }
}

