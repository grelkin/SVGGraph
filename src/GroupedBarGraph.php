<?php

namespace GGS\SVGGraph;

/**
 * Class GroupedBarGraph
 *
 * @property mixed group_space
 */
class GroupedBarGraph extends BarGraph
{
    protected function Draw()
    {
        $body = $this->Grid() . $this->UnderShapes();

        $chunk_count = count($this->multi_graph);
        list($chunk_width, $bspace, $chunk_unit_width) =
            self::BarPosition(
                $this->bar_width,
                $this->bar_width_min,
                $this->x_axes[$this->main_x_axis]->Unit(),
                $chunk_count,
                $this->bar_space,
                $this->group_space
            );

        $bar_style = array();
        $bar       = array('width' => $chunk_width);
        $this->ColourSetup($this->multi_graph->ItemsCount(-1), $chunk_count);

        $bnum       = 0;
        $bars_shown = array_fill(0, $chunk_count, 0);
        $bars       = '';
        foreach ($this->multi_graph as $itemlist) {
            $k       = $itemlist[0]->key;
            $bar_pos = $this->GridPosition($k, $bnum);
            if (!is_null($bar_pos)) {
                for ($j = 0; $j < $chunk_count; ++$j) {
                    $bar['x'] = $bspace + $bar_pos + ($j * $chunk_unit_width);
                    $item     = $itemlist[$j];
                    $this->SetStroke($bar_style, $item, $j);
                    $bar_style['fill'] = $this->GetColour($item, $bnum, $j);

                    if (!is_null($item->value)) {
                        $this->Bar($item->value, $bar, null, $this->DatasetYAxis($j));

                        if ($bar['height'] > 0) {
                            ++$bars_shown[$j];

                            $show_label = $this->AddDataLabel(
                                $j,
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
                    }
                    $this->bar_styles[$j] = $bar_style;
                }
            }
            ++$bnum;
        }
        if (!$this->legend_show_empty) {
            foreach ($bars_shown as $j => $bar) {
                if (!$bar) {
                    $this->bar_styles[$j] = null;
                }
            }
        }

        if ($this->semantic_classes) {
            $bars = $this->Element('g', array('class' => 'series'), null, $bars);
        }
        $body .= $bars;
        $body .= $this->OverShapes();
        $body .= $this->Axes();

        return $body;
    }

    /**
     * construct multigraph.
     *
     * @param $values
     */
    public function Values($values)
    {
        parent::Values($values);
        if (!$this->values->error) {
            $this->multi_graph = new MultiGraph(
                $this->values, $this->force_assoc,
                $this->require_integer_keys
            );
        }
    }

    /**
     * Calculates the bar width, gap to first bar, gap between bars
     * returns an array containing all three.
     * @param $bar_width
     * @param $bar_width_min
     * @param $unit_width
     * @param $group_size
     * @param $bar_space
     * @param $group_space
     * @return array
     */
    public static function BarPosition(
        $bar_width,
        $bar_width_min,
        $unit_width,
        $group_size,
        $bar_space,
        $group_space
    ) {
        if (is_numeric($bar_width) && $bar_width >= 1) {
            return self::BarPositionFixed(
                $bar_width,
                $unit_width,
                $group_size,
                $group_space
            );
        } else {
            // bar width dependent on space
            $gap_count = $group_size - 1;
            $gap       = $gap_count > 0 ? $group_space : 0;

            $bar_width = $bar_space >= $unit_width ? '1' : $unit_width - $bar_space;
            if ($gap_count > 0 && $gap * $gap_count > $bar_width - $group_size) {
                $gap = ($bar_width - $group_size) / $gap_count;
            }
            $bar_width = ($bar_width - ($gap * ($group_size - 1)))
                         / $group_size;

            if ($bar_width < $bar_width_min) {
                return self::BarPositionFixed(
                    $bar_width_min,
                    $unit_width,
                    $group_size,
                    $group_space
                );
            }
            $spacing = $bar_width + $gap;
            $offset  = $bar_space / 2;
        }

        return array($bar_width, $offset, $spacing);
    }

    /**
     * Calculate bar width, gaps, using fixed bar width.
     * @param $bar_width
     * @param $unit_width
     * @param $group_size
     * @param $group_space
     * @return array
     */
    public static function BarPositionFixed(
        $bar_width,
        $unit_width,
        $group_size,
        $group_space
    ) {
        $gap = $group_size > 1 ? $group_space : 0;
        if ($group_size > 1 && ($bar_width + $gap) * $group_size > $unit_width) {

            // bars don't fit with group_space option, so they must overlap
            // (and make sure the bars are at least 1 pixel apart)
            $spacing = max(1, ($unit_width - $bar_width) / ($group_size - 1));
            $offset  = 0;
        } else {
            // space the bars group_space apart, centred in unit space
            $spacing = $bar_width + $gap;
            $offset  = max(0, ($unit_width - ($spacing * $group_size)) / 2);
        }

        return array($bar_width, $offset, $spacing);
    }
}
