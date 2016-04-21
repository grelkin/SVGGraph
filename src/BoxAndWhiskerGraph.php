<?php

namespace GGS\SVGGraph;

/**
 * Class BoxAndWhiskerGraph
 *
 * @property mixed whisker_width
 * @property mixed median_stroke_width
 * @property mixed whisker_dash
 */
class BoxAndWhiskerGraph extends PointGraph
{
    protected $label_centre = true;
    protected $require_structured = array('top', 'bottom', 'wtop', 'wbottom');
    private $min_value = null;
    private $max_value = null;
    private $box_styles = array();

    protected function Draw()
    {
        $body = $this->Grid() . $this->UnderShapes();

        $bar_width = $this->BarWidth();
        $x_axis    = $this->x_axes[$this->main_x_axis];
        $box_style = array();

        $bSpace = max(0, ($this->x_axes[$this->main_x_axis]->Unit() - $bar_width) / 2);
        $bNum   = 0;
        $this->ColourSetup($this->values->ItemsCount());
        $series = '';
        foreach ($this->values[0] as $item) {
            $bar_pos = $this->GridPosition($item->key, $bNum);

            if (!is_null($item->value) && !is_null($bar_pos)) {
                $box_style['fill'] = $this->GetColour($item, $bNum);
                $this->SetStroke($box_style, $item);
                $shape = $this->WhiskerBox(
                    $bSpace + $bar_pos,
                    $bar_width,
                    $item->value,
                    $item->Data('top'),
                    $item->Data('bottom'),
                    $item->Data('wtop'),
                    $item->Data('wbottom')
                );

                // wrap the whisker box in a group
                $g = array();
                $this->AddDataLabel(
                    0,
                    $bNum,
                    $g,
                    $item,
                    $bSpace + $bar_pos,
                    $this->GridY($item->Data('top')),
                    $bar_width,
                    $this->GridY($item->Data('bottom')) - $this->GridY($item->Data('top'))
                );
                if ($this->show_tooltips) {
                    $this->SetTooltip(
                        $g,
                        $item,
                        0,
                        $item->key,
                        $item->value,
                        !$this->compat_events && $this->show_label
                    );
                }

                if ($this->semantic_classes) {
                    $g['class'] = 'series0';
                }
                $group = $this->Element('g', array_merge($g, $box_style), null, $shape);
                $series .= $this->GetLink($item, $item->key, $group);
                $this->box_styles[$bNum] = $box_style;

                // add outliers as markers
                $x = $bar_pos + $x_axis->Unit() / 2;
                foreach ($this->GetOutliers($item) as $oValue) {
                    $y = $this->GridY($oValue);
                    $this->AddMarker($x, $y, $item);
                }
            }
            ++$bNum;
        }

        if ($this->semantic_classes) {
            $series = $this->Element('g', array('class' => 'series'), null, $series);
        }
        $body .= $series;
        $body .= $this->OverShapes();
        $body .= $this->Axes();
        $body .= $this->DrawMarkers();

        return $body;
    }

    /**
     * Returns the width of a bar.
     */
    protected function BarWidth()
    {
        if (is_numeric($this->bar_width) && $this->bar_width >= 1) {
            return $this->bar_width;
        }
        $unit_w = $this->x_axes[$this->main_x_axis]->Unit();
        $bw     = $unit_w - $this->bar_space;

        return max(1, $bw, $this->bar_width_min);
    }

    /**
     * Returns the code for a box with whiskers.
     *
     * @param $x
     * @param $w
     * @param $median
     * @param $top
     * @param $bottom
     * @param $wtop
     * @param $wbottom
     *
     * @return string
     */
    protected function WhiskerBox(
        $x,
        $w,
        $median,
        $top,
        $bottom,
        $wtop,
        $wbottom
    ) {
        $t  = $this->GridY($top);
        $b  = $this->GridY($bottom);
        $wt = $this->GridY($wtop);
        $wb = $this->GridY($wbottom);

        $box  = array('x' => $x, 'y' => $t, 'width' => $w, 'height' => $b - $t);
        $rect = $this->Element('rect', $box);

        // whisker lines
        $lg      = $w * (1 - $this->whisker_width) * 0.5;
        $ll      = $x + $lg;
        $lr      = $x + $w - $lg;
        $l       = array('x1' => $ll, 'x2' => $lr);
        $l['y1'] = $l['y2'] = $wt;
        $l1      = $this->Element('line', $l);
        $l['y1'] = $l['y2'] = $wb;
        $l2      = $this->Element('line', $l);

        // median line
        $l['x1'] = $x;
        $l['x2'] = $x + $w;
        $l['y1'] = $l['y2'] = $this->GridY($median);
        $style   = array('stroke-width' => $this->median_stroke_width);
        $l3      = $this->Element('line', array_merge($l, $style));

        // whisker dashed lines
        $style   = array('stroke-dasharray' => $this->whisker_dash);
        $l['x1'] = $l['x2'] = $x + $w / 2;
        $l['y1'] = $wt;
        $l['y2'] = $t;
        $w1      = $this->Element('line', array_merge($l, $style));
        $l['y1'] = $wb;
        $l['y2'] = $b;
        $w2      = $this->Element('line', array_merge($l, $style));

        return $rect . $w1 . $w2 . $l1 . $l2 . $l3;
    }

    /**
     * Checks that the data contains sensible values.
     */
    protected function CheckValues()
    {
        parent::CheckValues();

        foreach ($this->values[0] as $item) {
            if (is_null($item->value)) {
                continue;
            }
            $wb = $item->Data('wbottom');
            $wt = $item->Data('wtop');
            $b  = $item->Data('bottom');
            $t  = $item->Data('top');
            if ($wb > $b || $wt < $t || $item->value < $b || $item->value > $t) {
                throw new \Exception("Data problem: $wb $b {$item->value} $t $wt");
            }
        }
    }

    /**
     * Return box for legend.
     *
     * @param $set
     * @param $x
     * @param $y
     * @param $w
     * @param $h
     *
     * @return string
     */
    public function DrawLegendEntry($set, $x, $y, $w, $h)
    {
        if (!isset($this->box_styles[$set])) {
            return '';
        }

        $box = array('x' => $x, 'y' => $y, 'width' => $w, 'height' => $h);

        return $this->Element('rect', $box, $this->box_styles[$set]);
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
            if (is_null($item->value)) {
                continue;
            }
            $points = array($item->Data('wtop'));
            $points = array_merge($points, $this->GetOutliers($item));
            $m      = max($points);
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
            if (is_null($item->value)) {
                continue;
            }
            $points = array($item->Data('wbottom'));
            $points = array_merge($points, $this->GetOutliers($item));
            $m      = min($points);
            if (is_null($min) || $m < $min) {
                $min = $m;
            }
        }

        return ($this->min_value = $min);
    }

    /**
     * Returns the list of outliers for an item.
     *
     * @param SVGGraphDataItem|SVGGraphStructuredDataItem $item
     *
     * @return array
     */
    protected function GetOutliers(&$item)
    {
        $outliers = array();
        if (!isset($this->structure['outliers']) ||
            !is_array($this->structure['outliers'])
        ) {
            return $outliers;
        }

        $min = $item->Data('wbottom');
        $max = $item->Data('wtop');
        foreach ($this->structure['outliers'] as $o) {
            $v = $item->RawData($o);
            if (!is_null($v) && ($v > $max || $v < $min)) {
                $outliers[] = $v;
            }
        }

        return $outliers;
    }
}
