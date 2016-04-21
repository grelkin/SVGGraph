<?php

namespace GGS\SVGGraph;

/**
 * Class CylinderGraph
 *
 * @property string   shade_gradient_id
 * @property string[] depth_shade_gradient
 */
class CylinderGraph extends Bar3DGraph
{
    protected $bar_styles = array();
    protected $label_centre = true;
    protected $transform;
    protected $tx;
    protected $ty;
    protected $arc_path;
    protected $cyl_offset_x;
    protected $cyl_offset_y;

    /**
     * Sets up the cylinder dimensions.
     */
    protected function SetupCylinder()
    {
        // translation for the whole cylinder
        list($sx, $sy) = $this->Project(0, 0, $this->block_width);
        $tx              = ($this->block_width + $sx) / 2;
        $ty              = $sy / 2;
        $this->tx        = $tx;
        $this->ty        = $ty;
        $this->transform = "translate($tx,$ty)";

        // use the ellipse info to create the bottom arc
        $ellipse            = $this->FindEllipse($this->project_angle, $this->block_width);
        $r                  = -$this->project_angle / 2;
        $rr                 = deg2rad($r);
        $x1                 = -($ellipse['x1'] * cos($rr) + $ellipse['y1'] * sin($rr));
        $y1                 = -($ellipse['x1'] * sin($rr) - $ellipse['y1'] * cos($rr));
        $x2                 = -2 * $x1;
        $y2                 = -2 * $y1;
        $this->cyl_offset_x = $x1;
        $this->cyl_offset_y = $y1;
        $a                  = $ellipse['a'];
        $b                  = $ellipse['b'];
        $this->arc_path     = "a$a $b $r 1 0 $x2 $y2";

        // set the gradient overlay
        $this->shade_gradient_id = is_array($this->depth_shade_gradient) ?
            $this->AddGradient($this->depth_shade_gradient) : 0;
    }

    /**
     * Creates the ellipse for the top of the cylinder.
     *
     * @param $ellipse
     * @param $angle
     *
     * @return array
     */
    protected function CreateEllipse($ellipse, $angle)
    {
        $top_id = $this->NewID();
        $r      = -$angle / 2;
        $top    = array(
            'id'        => $top_id,
            'cx'        => 0,
            'cy'        => 0,
            'rx'        => $ellipse['a'],
            'ry'        => $ellipse['b'],
            'transform' => "rotate({$r})",
        );

        $this->defs[] = $this->Element(
            'symbol',
            null,
            null,
            $this->Element('ellipse', $top)
        );

        return array('xlink:href' => '#' . $top_id);
    }

    /**
     * Calculates the a and b radii of the ellipse filling the parallelogram.
     *
     * @param $angle
     * @param $length
     *
     * @return array
     */
    protected function FindEllipse($angle, $length)
    {
        $alpha = deg2rad($angle / 2);
        $x     = $length * cos($alpha) / 2;
        $y     = $length * sin($alpha) / 2;
        $dYdX  = -$y / $x;

        $bsq = pow($y, 2) - $x * $y * $dYdX;
        $asq = pow($x, 2) / (1 - $y / ($y - $x * $dYdX));

        $a = sqrt($asq);
        $b = sqrt($bsq);

        // now find the vertical
        $alpha2 = deg2rad(-$angle / 2 - 90);
        $dydx2  = tan($alpha2);
        $ysq    = $bsq / (pow($dydx2, 2) * ($asq / $bsq) + 1);
        $xsq    = $asq - $asq * $ysq / $bsq;

        $x1 = sqrt($xsq);
        $y1 = -sqrt($ysq);

        return compact('a', 'b', 'x1', 'y1');
    }

    /**
     * Create the top ellipse.
     */
    protected function BarTop()
    {
        $ellipse = $this->FindEllipse($this->project_angle, $this->block_width);

        return $this->CreateEllipse($ellipse, $this->project_angle);
    }

    /**
     * Returns the SVG code for a 3D cylinder.
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
        if (is_null($this->arc_path)) {
            $this->SetupCylinder();
        }
        $pos = $this->Bar($item->value, $bar, $start, $axis);
        if (is_null($pos) || $pos > $this->height - $this->pad_bottom) {
            return '';
        }

        if (is_null($top)) {
            $cyl_top = '';
        } else {
            $top['transform'] = "translate({$bar['x']},{$bar['y']})";
            $top['fill']      = $this->GetColour($item, $index, $dataset, true);
            $cyl_top          = $this->Element('use', $top, null, $this->empty_use ? '' : null);
        }

        $group = array('transform' => $this->transform);

        $x             = $bar['x'] + $this->cyl_offset_x;
        $y             = $bar['y'] + $this->cyl_offset_y;
        $h             = $bar['height'];
        $side          = array('d' => "M{$x} {$y}v{$h}{$this->arc_path}v-{$h}z");
        $group['fill'] = $this->GetColour($item, $index, $dataset);

        $cyl_side = $this->Element('path', $side);

        if (!empty($this->shade_gradient_id)) {
            $side['fill'] = "url(#{$this->shade_gradient_id})";
            $cyl_side .= $this->Element('path', $side);
        }

        return $this->Element('g', $group, null, $cyl_side . $cyl_top);
    }
}
