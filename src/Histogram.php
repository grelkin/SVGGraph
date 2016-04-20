<?php

namespace GGS\SVGGraph;

class Histogram extends BarGraph
{
    protected $label_centre = false;
    protected $repeated_keys = 'accept'; // allow repeated keys

    protected $increment = null;
    protected $percentage = false;

    /**
     * Process the values.
     *
     * @param $values
     */
    public function Values($values)
    {
        if (!empty($values)) {
            parent::Values($values);
            $values = array();

            // find min, max, strip out nulls
            $min = $max = null;
            foreach ($this->values[0] as $item) {
                if (!is_null($item->value)) {
                    if (is_null($min) || $item->value < $min) {
                        $min = $item->value;
                    }
                    if (is_null($max) || $item->value > $max) {
                        $max = $item->value;
                    }
                    $values[] = $item->value;
                }
            }

            // calculate increment?
            if ($this->increment <= 0) {
                $diff = $max - $min;
                if ($diff <= 0) {
                    $inc = 1;
                } else {
                    $inc = pow(10, floor(log10($diff)));
                    $d1  = $diff / $inc;
                    if (($inc != 1 || !is_integer($diff)) && $d1 < 4) {
                        if ($d1 < 3) {
                            $inc *= 0.2;
                        } else {
                            $inc *= 0.5;
                        }
                    }
                }
                $this->increment = $inc;
            }

            // prefill the map with nulls
            $map   = array();
            $start = $this->Interval($min);
            $end   = $this->Interval($max, true) + $this->increment / 2;

            Graph::SetNumStringOptions(
                $this->settings['decimal'],
                $this->settings['thousands']
            );
            for ($i = $start; $i < $end; $i += $this->increment) {
                $key       = (int)$i;
                $map[$key] = null;
            }

            foreach ($values as $val) {
                $k = (int)$this->Interval($val);
                if (!array_key_exists($k, $map)) {
                    $map[$k] = 1;
                } else {
                    $map[$k]++;
                }
            }

            if ($this->percentage) {
                $total = count($values);
                $pmap  = array();
                foreach ($map as $k => $v) {
                    $pmap[$k] = 100 * $v / $total;
                }
                $values = $pmap;
            } else {
                $values = $map;
            }

            // turn off structured data
            $this->structure       = null;
            $this->structured_data = false;

            // set up options to make bar graph class draw the histogram properly
            $this->minimum_units_y = 1;
            $this->subdivision_h   = $this->increment; // no subdiv below bar size
            $this->grid_division_h = max($this->increment, $this->grid_division_h);

            $amh = $this->axis_min_h;
            if (empty($amh)) {
                $this->axis_min_h = $start;
            }
        }
        parent::Values($values);
    }

    /**
     * Returns the start (or next) interval for a value.
     * @param      $value
     * @param bool $next
     * @return float
     */
    public function Interval($value, $next = false)
    {
        $n = floor($value / $this->increment);
        if ($next) {
            ++$n;
        }

        return $n * $this->increment;
    }

    /**
     * Sets up the colour class with corrected number of colours.
     * @param      $count
     * @param null $datasets
     */
    protected function ColourSetup($count, $datasets = null)
    {
        // $count is off by 1 because the divisions are numbered
        return parent::ColourSetup($count - 1, $datasets);
    }

    /**
     * Override because of the shifted numbering.
     * @param $key
     * @param $ikey
     * @return null
     */
    protected function GridPosition($key, $ikey)
    {
        $position = null;
        $zero     = -0.01; // catch values close to 0
        $axis     = $this->x_axes[$this->main_x_axis];
        $offset   = $axis->Zero() + ($axis->Unit() * $key);
        $g_limit  = $this->g_width - ($axis->Unit() / 2);
        if ($offset >= $zero && floor($offset) <= $g_limit) {
            $position = $this->pad_left + $offset;
        }

        return $position;
    }

    /**
     * Returns the width of a bar.
     */
    protected function BarWidth()
    {
        if (is_numeric($this->bar_width) && $this->bar_width >= 1) {
            return $this->bar_width;
        }
        $unit_w = $this->increment * $this->x_axes[$this->main_x_axis]->Unit();

        return max(1, $unit_w - $this->bar_space);
    }

    /**
     * Returns the space before a bar.
     * @param $bar_width
     * @return mixed
     */
    protected function BarSpace($bar_width)
    {
        $uwidth = $this->increment * $this->x_axes[$this->main_x_axis]->Unit();

        return max(0, ($uwidth - $bar_width) / 2);
    }
}
