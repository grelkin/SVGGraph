<?php

namespace GGS\SVGGraph;

/**
 * MultiBezierGraph - joined line, with axes and grid.
 */
class MultiBezierGraph extends BezierGraph
{
    protected $require_integer_keys = false;

    protected function Draw()
    {
        $body = $this->Grid() . $this->Guidelines(SVGG_GUIDELINE_BELOW);

        $plots      = '';
        $y_axis_pos = $this->height - $this->pad_bottom -
                      $this->y_axes[$this->main_y_axis]->Zero();
        $y_bottom   = min($y_axis_pos, $this->height - $this->pad_bottom);

        $chunk_count = count($this->multi_graph);
        $this->ColourSetup($this->multi_graph->ItemsCount(-1), $chunk_count);

        for ($i = 0; $i < $chunk_count; ++$i) {
            $bnum   = 0;
            $points = array();
            $axis   = $this->DatasetYAxis($i);
            foreach ($this->multi_graph[$i] as $item) {
                $x = $this->GridPosition($item->key, $bnum);
                if (!is_null($x) && !is_null($item->value)) {
                    $y        = $this->GridY($item->value, $axis);
                    $points[] = array($x, $y, $item, $i, $bnum);
                }
                ++$bnum;
            }

            $plot = $this->DrawLine($i, $points, $y_bottom);
            if ($this->semantic_classes) {
                $plots .= $this->Element('g', array('class' => 'series'), null, $plot);
            } else {
                $plots .= $plot;
            }
        }

        $group = array();
        $this->ClipGrid($group);

        $body .= $this->Element('g', $group, null, $plots);
        $body .= $this->Guidelines(SVGG_GUIDELINE_ABOVE);
        $body .= $this->Axes();
        $body .= $this->CrossHairs();
        $body .= $this->DrawMarkers();

        return $body;
    }

    /**
     * construct multigraph.
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
}
