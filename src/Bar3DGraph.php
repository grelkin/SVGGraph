<?php

namespace GGS\SVGGraph;

/**
 * Class Bar3DGraph
 *
 * @property mixed bar_width
 * @property mixed bar_space
 * @property mixed bar_width_min
 * @property mixed skew_top
 * @property mixed skew_side
 * @property mixed bar_side_overlay_opacity
 * @property mixed bar_side_overlay_colour
 * @property mixed bar_top_overlay_colour
 * @property mixed bar_front_overlay_colour
 * @property mixed bar_front_overlay_opacity
 */
class Bar3DGraph extends ThreeDGraph
{
    protected $label_centre = true;
    protected $bar_styles = array();
    protected $bx;
    protected $by;
    protected $block_width;

    protected function Draw()
    {
        $body      = $this->Grid() . $this->UnderShapes();
        $bar_width = $this->block_width = $this->BarWidth();

        // make the top parallelogram, set it as a symbol for re-use
        list($this->bx, $this->by) = $this->Project(0, 0, $bar_width);
        $top = $this->BarTop();

        $bNum   = 0;
        $bSpace = max(0, ($this->x_axes[$this->main_x_axis]->Unit() - $bar_width) / 2);
        $this->ColourSetup($this->values->ItemsCount());

        // get the translation for the whole bar
        list($tx, $ty) = $this->Project(0, 0, $bSpace);
        $all_group = array();
        if ($tx || $ty) {
            $all_group['transform'] = "translate($tx,$ty)";
        }

        $bar   = array('width' => $bar_width);
        $bars  = '';
        $group = array();
        if ($this->semantic_classes) {
            $all_group['class'] = 'series';
            $group['class']     = 'series0';
        }
        foreach ($this->values[0] as $item) {
            $bar_pos = $this->GridPosition($item->key, $bNum);

            if ($this->legend_show_empty || !is_null($item->value)) {
                $bar_style = array('fill' => $this->GetColour($item, $bNum));
                $this->SetStroke($bar_style, $item);
            } else {
                $bar_style = null;
            }
            $this->bar_styles[] = $bar_style;

            if (!is_null($item->value) && !is_null($bar_pos)) {
                $bar['x'] = $bSpace + $bar_pos;

                $bar_sections = $this->Bar3D($item, $bar, $top, $bNum);
                if ($bar_sections != '') {
                    $this->AddDataLabel(
                        0,
                        $bNum,
                        $group,
                        $item,
                        $bar['x'] + $tx,
                        $bar['y'] + $ty,
                        $bar['width'],
                        $bar['height']
                    );
                    $link = $this->GetLink($item, $item->key, $bar_sections);

                    $group = array_merge($group, $bar_style);
                    if ($this->show_tooltips) {
                        $this->SetTooltip($group, $item, 0, $item->key, $item->value);
                    }
                    $this->SetStroke($group, $item, 0, 'round');
                    $bars .= $this->Element('g', $group, null, $link);
                    unset($group['id']); // make sure a new one is generated
                }
            }
            ++$bNum;
        }

        if (count($all_group)) {
            $bars = $this->Element('g', $all_group, null, $bars);
        }
        $body .= $bars;
        $body .= $this->OverShapes();
        $body .= $this->Axes();

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
     * Returns the bar top path details array.
     */
    protected function BarTop()
    {
        $bw      = $this->block_width;
        $top_id  = $this->NewID();
        $g       = array('id' => $top_id);
        $bar_top = '';

        if ($this->skew_top) {
            $sc      = abs($this->by / $bw);
            $a       = 90 - $this->project_angle;
            $top     = array(
                'd'         => "M0,0 l0,-{$bw} l{$bw},0 l0,{$bw} z",
                'transform' => "skewX(-{$a}) scale(1,{$sc})",
                'stroke'    => 'none',
            );
            $bar_top = $this->Element('path', $top);
        }
        $top = array('d' => "M0,0 l{$bw},0 l{$this->bx},{$this->by} l-{$bw},0 z");
        if ($this->skew_top) {
            $top['fill'] = 'none';
        }
        $bar_top .= $this->Element('path', $top);
        $this->defs[] = $this->Element(
            'symbol',
            null,
            null,
            $this->Element('g', $g, null, $bar_top)
        );

        return array('xlink:href' => '#' . $top_id);
    }

    /**
     * Returns the SVG code for a 3D bar.
     *
     * @param      $item
     * @param      $bar
     * @param      $top
     * @param      $index
     * @param null $dataset
     * @param null $start
     * @param null $axis
     *
     * @return string
     */
    protected function Bar3D(
        $item,
        &$bar,
        &$top,
        $index,
        $dataset = null,
        $start = null,
        $axis = null
    ) {
        $pos = $this->Bar($item->value, $bar, $start, $axis);
        if (is_null($pos) || $pos > $this->height - $this->pad_bottom) {
            return '';
        }

        $side_overlay  = min(1, max(0, $this->bar_side_overlay_opacity));
        $top_overlay   = min(1, max(0, $this->bar_top_overlay_opacity));
        $front_overlay = min(1, max(0, $this->bar_front_overlay_opacity));

        $bar_side = '';
        $bw       = $this->block_width;
        $bh       = $bar['height'];
        $side_x   = $bar['x'] + $bw;
        if ($this->skew_side) {
            $sc       = $this->bx / $bw;
            $a        = $this->project_angle;
            $side     = array(
                'd'         => "M0,0 L{$bw},0 l0,{$bh} l-{$bw},0 z",
                'transform' => "translate($side_x,{$bar['y']}) skewY(-{$a}) scale({$sc},1)",
                'stroke'    => 'none',
            );
            $bar_side = $this->Element('path', $side);
        }
        $side = array(
            'd'         => "M0,0 l{$this->bx},{$this->by} l0,{$bh} l-{$this->bx}," . -$this->by . ' z',
            'transform' => "translate($side_x,$bar[y])",
        );
        if ($this->skew_side) {
            $side['fill'] = 'none';
        }
        if ($side_overlay) {
            $side['stroke'] = 'none';
        } // only stroke top layer
        $bar_side .= $this->Element('path', $side);

        if ($side_overlay) {
            $side['fill-opacity'] = $side_overlay;
            $side['fill']         = $this->bar_side_overlay_colour;
            unset($side['stroke']);
            $bar_side .= $this->Element('path', $side);
        }

        if (is_null($top)) {
            $bar_top = '';
        } else {
            $top['transform'] = "translate($bar[x],$bar[y])";
            $top['fill']      = $this->GetColour(
                $item,
                $index,
                $dataset,
                $this->skew_top ? false : true
            );
            if ($top_overlay) {
                $top['stroke'] = 'none';
            }
            $bar_top = $this->Element('use', $top, null, $this->empty_use ? '' : null);

            if ($top_overlay) {
                unset($top['stroke']);
                $oTop                 = $top;
                $oTop['fill-opacity'] = $top_overlay;
                $oTop['fill']         = $this->bar_top_overlay_colour;
                $bar_top .= $this->Element('use', $oTop, null, $this->empty_use ? '' : null);
            }
        }

        if ($front_overlay) {
            $bar['stroke'] = 'none';
        }
        $rect = $this->Element('rect', $bar);

        if ($front_overlay) {
            unset($bar['stroke']);
            $obar                 = $bar;
            $obar['fill-opacity'] = $front_overlay;
            $obar['fill']         = $this->bar_front_overlay_colour;
            $rect .= $this->Element('rect', $obar);
        }

        return $rect . $bar_top . $bar_side;
    }

    /**
     * Fills in the y-position and height of a bar (copied from BarGraph).
     *
     * @param number $value value
     * @param array  &$bar  element array [out]
     * @param number $start start value
     * @param number $axis  axis number
     *
     * @return number unclamped bar position
     */
    protected function Bar($value, &$bar, $start = null, $axis = null)
    {
        if ($start) {
            $value += $start;
        }

        $startPos = is_null($start)
            ? $this->OriginY($axis)
            : $this->GridY($start, $axis);

        $pos = $this->GridY($value, $axis);
        if (is_null($pos)) {
            $bar['height'] = 0;
        } else {
            $l1            = $this->ClampVertical($startPos);
            $l2            = $this->ClampVertical($pos);
            $bar['y']      = min($l1, $l2);
            $bar['height'] = abs($l1 - $l2);
        }

        return $pos;
    }

    /**
     * Override to check minimum space requirement.
     *
     * @param      $dataset
     * @param      $index
     * @param      $element
     * @param      $item
     * @param      $x
     * @param      $y
     * @param      $w
     * @param      $h
     * @param null $content
     * @param bool $duplicate
     *
     * @return bool
     */
    protected function AddDataLabel(
        $dataset,
        $index,
        &$element,
        &$item,
        $x,
        $y,
        $w,
        $h,
        $content = null,
        $duplicate = true
    ) {
        if ($h < $this->ArrayOption($this->data_label_min_space, $dataset)) {
            return false;
        }

        return parent::AddDataLabel(
            $dataset,
            $index,
            $element,
            $item,
            $x,
            $y,
            $w,
            $h,
            $content,
            $duplicate
        );
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
    protected function DrawLegendEntry($set, $x, $y, $w, $h)
    {
        if (!isset($this->bar_styles[$set])) {
            return '';
        }

        $bar = array('x' => $x, 'y' => $y, 'width' => $w, 'height' => $h);

        return $this->Element('rect', $bar, $this->bar_styles[$set]);
    }
}
