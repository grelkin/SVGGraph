<?php

namespace GGS\SVGGraph;

/**
 * Class BarAndLineGraph
 *
 * @property mixed line_dataset
 */
class BarAndLineGraph extends GroupedBarGraph
{
    /**
     * @var LineGraph|null
     */
    protected $lineGraph = null;

    /**
     * We need an instance of the LineGraph class.
     *
     * @param      $w
     * @param      $h
     * @param null $settings
     */
    public function __construct($w, $h, $settings = null)
    {
        parent::__construct($w, $h, $settings);

        // prevent repeated labels
        unset($settings['label']);
        $this->lineGraph = new LineGraph($w, $h, $settings);
    }

    protected function Draw()
    {
        $body = $this->Grid() . $this->UnderShapes();

        // LineGraph has not been initialised, need to copy in details
        $copy = array(
            'colours',
            'links',
            'x_axes',
            'y_axes',
            'main_x_axis',
            'main_y_axis',
        );
        foreach ($copy as $member) {
            $this->lineGraph->{$member} = $this->{$member};
        }

        // keep gradients and patterns synced
        $this->lineGraph->gradients    = &$this->gradients;
        $this->lineGraph->pattern_list = &$this->pattern_list;
        $this->lineGraph->defs         = &$this->defs;

        // find the lines and reduce the bar count by the number of lines
        $bar_count = $chunk_count = count($this->multi_graph);
        $lines     = $this->line_dataset;
        if (!is_array($lines)) {
            $lines = array($lines);
        }
        rsort($lines);
        foreach ($lines as $line) {
            if ($line < $bar_count) {
                --$bar_count;
            }
        }
        $lines = array_flip($lines);

        $y_axis_pos = $this->height - $this->pad_bottom -
                      $this->y_axes[$this->main_y_axis]->Zero();
        $y_bottom   = min($y_axis_pos, $this->height - $this->pad_bottom);

        if ($bar_count == 0) {
            $chunk_width = $bSpace = $chunk_unit_width = 1;
        } else {
            // this would have problems if there are no bars
            list($chunk_width, $bSpace, $chunk_unit_width) =
                GroupedBarGraph::BarPosition(
                    $this->bar_width,
                    $this->bar_width_min,
                    $this->x_axes[$this->main_x_axis]->Unit(),
                    $bar_count,
                    $this->bar_space,
                    $this->group_space
                );
        }

        $bar_style = array();
        $bar       = array('width' => $chunk_width);
        $this->ColourSetup($this->multi_graph->ItemsCount(-1), $chunk_count);
        $marker_offset = $this->x_axes[$this->main_x_axis]->Unit() / 2;

        $bNum       = 0;
        $bars_shown = array_fill(0, $chunk_count, 0);
        $bars       = '';

        // draw bars, store line points
        $points = array();
        foreach ($this->multi_graph as $itemlist) {
            $k       = $itemlist[0]->key;
            $bar_pos = $this->GridPosition($k, $bNum);
            if (!is_null($bar_pos)) {
                for ($j = 0, $b = 0; $j < $chunk_count; ++$j) {
                    $y_axis = $this->DatasetYAxis($j);
                    $item   = $itemlist[$j];

                    if (array_key_exists($j, $lines)) {
                        if (!is_null($item->value)) {
                            $x            = $bar_pos + $marker_offset;
                            $y            = $this->GridY($item->value, $y_axis);
                            $points[$j][] = array($x, $y, $item, $j, $bNum);
                        }
                    } else {
                        $bar['x'] = $bSpace + $bar_pos + ($b * $chunk_unit_width);
                        $this->SetStroke($bar_style, $item, $j);
                        $bar_style['fill'] = $this->GetColour($item, $bNum, $j);

                        if (!is_null($item->value)) {
                            $this->Bar($item->value, $bar, null, $y_axis);

                            if ($bar['height'] > 0) {
                                ++$bars_shown[$j];

                                $show_label = $this->AddDataLabel(
                                    $j,
                                    $bNum,
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
                                        $j,
                                        $item->key,
                                        $item->value,
                                        !$this->compat_events && $show_label
                                    );
                                }
                                if ($this->semantic_classes) {
                                    $bar['class'] = "series{$j}";
                                }
                                $rect = $this->Element('rect', $bar, $bar_style);
                                $bars .= $this->GetLink($item, $k, $rect);
                                unset($bar['id']); // clear for next generated value
                            }
                            ++$b;
                        }
                        $this->bar_styles[$j] = $bar_style;
                    }
                }
            }
            ++$bNum;
        }

        // draw lines clipped to grid
        $graph_line = '';
        foreach ($points as $dataset => $p) {
            $graph_line .= $this->lineGraph->DrawLine($dataset, $p, $y_bottom);
        }
        $group = array();
        $this->ClipGrid($group);
        $bars .= $this->Element('g', $group, null, $graph_line);

        if ($this->semantic_classes) {
            $bars = $this->Element('g', array('class' => 'series'), null, $bars);
        }
        $body .= $bars;

        if (!$this->legend_show_empty) {
            foreach ($bars_shown as $j => $bar) {
                if (!$bar) {
                    $this->bar_styles[$j] = null;
                }
            }
        }

        $body .= $this->OverShapes();
        $body .= $this->Axes();

        // add in the markers created by line graph
        $body .= $this->lineGraph->DrawMarkers();

        return $body;
    }

    /**
     * Return box or line for legend.
     *
     * @param $set
     * @param $x
     * @param $y
     * @param $w
     * @param $h
     *
     * @return string
     */
    protected function DrawLegendEntry($set, $x, $y, $w, $h)
    {
        if (isset($this->bar_styles[$set])) {
            return parent::DrawLegendEntry($set, $x, $y, $w, $h);
        }

        return $this->lineGraph->DrawLegendEntry($set, $x, $y, $w, $h);
    }

    /**
     * Draws this graph's data labels, and the line graph's data labels.
     */
    protected function DrawDataLabels()
    {
        $labels = parent::DrawDataLabels();
        $labels .= $this->lineGraph->DrawDataLabels();

        return $labels;
    }
}
