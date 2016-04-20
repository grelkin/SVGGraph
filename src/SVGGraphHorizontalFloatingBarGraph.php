<?php

namespace GGS\SVGGraph;

class HorizontalFloatingBarGraph extends HorizontalBarGraph
{
    protected $require_structured = array('end');
    private $min_value = null;
    private $max_value = null;

    protected function Draw()
    {
        $body = $this->Grid() . $this->UnderShapes();

        $bar_height = $this->BarHeight();
        $bar_style  = array();
        $bar        = array('height' => $bar_height);

        $bspace = max(0, ($this->y_axes[$this->main_y_axis]->Unit() - $bar_height) / 2);
        $bnum   = 0;
        $this->ColourSetup($this->values->ItemsCount());
        $series = '';
        foreach ($this->values[0] as $item) {
            $bar_pos = $this->GridPosition($item->key, $bnum);

            if (!is_null($item->value) && !is_null($bar_pos)) {
                $bar['y'] = $bar_pos - $bspace - $bar_height;

                $end   = $item->Data('end');
                $start = $item->value;
                $value = $end - $start;
                $this->Bar($value, $bar, $start);

                if ($bar['width'] > 0) {
                    $bar_style['fill'] = $this->GetColour($item, $bnum);
                    $this->SetStroke($bar_style, $item);
                    $show_label = $this->AddDataLabel(
                        0,
                        $bnum,
                        $bar,
                        $item,
                        $bar['x'],
                        $bar['y'],
                        $bar['width'],
                        $bar['height']
                    );

                    if ($this->show_tooltips) {
                        $this->SetTooltip(
                            $bar,
                            $item,
                            0,
                            $item->key,
                            $value,
                            !$this->compat_events && $show_label
                        );
                    }
                    if ($this->semantic_classes) {
                        $bar['class'] = 'series0';
                    }
                    $rect = $this->Element('rect', $bar, $bar_style);
                    $series .= $this->GetLink($item, $item->key, $rect);
                    unset($bar['id']); // clear for next value

                    if (!isset($this->bar_styles[$bnum])) {
                        $this->bar_styles[$bnum] = $bar_style;
                    }
                }
            }
            ++$bnum;
        }

        if ($this->semantic_classes) {
            $series = $this->Element('g', array('class' => 'series'), null, $series);
        }
        $body .= $series;
        $body .= $this->OverShapes();
        $body .= $this->Axes();

        return $body;
    }

    /**
     * Checks that the data contains required fields.
     */
    protected function CheckValues()
    {
        parent::CheckValues();
    }

    /**
     * Returns the maximum bar end.
     */
    protected function GetMaxValue()
    {
        if (!is_null($this->max_value)) {
            return $this->max_value;
        }
        $max = null;
        foreach ($this->values[0] as $item) {
            $s = $item->value;
            $e = $item->Data('end');
            if (is_null($s) || is_null($e)) {
                continue;
            }
            $m = max($s, $e);
            if (is_null($max) || $m > $max) {
                $max = $m;
            }
        }

        return ($this->max_value = $max);
    }

    /**
     * Returns the minimum bar end.
     */
    protected function GetMinValue()
    {
        if (!is_null($this->min_value)) {
            return $this->min_value;
        }
        $min = null;
        foreach ($this->values[0] as $item) {
            $s = $item->value;
            $e = $item->Data('end');
            if (is_null($s) || is_null($e)) {
                continue;
            }
            $m = min($s, $e);
            if (is_null($min) || $m < $min) {
                $min = $m;
            }
        }

        return ($this->min_value = $min);
    }
}
