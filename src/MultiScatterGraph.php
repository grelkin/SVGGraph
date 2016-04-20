<?php

namespace GGS\SVGGraph;

/**
 * MultiScatterGraph - points with axes and grid.
 */
class MultiScatterGraph extends PointGraph
{
    protected $repeated_keys = 'accept';
    protected $require_integer_keys = false;

    protected function Draw()
    {
        $body = $this->Grid() . $this->UnderShapes();

        // a scatter graph without markers is empty!
        if ($this->marker_size == 0) {
            $this->marker_size = 1;
        }

        $chunk_count = count($this->multi_graph);
        $this->ColourSetup($this->multi_graph->ItemsCount(-1), $chunk_count);
        $best_fit_above = $best_fit_below = '';
        for ($i = 0; $i < $chunk_count; ++$i) {
            $bnum = 0;
            $axis = $this->DatasetYAxis($i);
            foreach ($this->multi_graph[$i] as $item) {
                $x = $this->GridPosition($item->key, $bnum);
                if (!is_null($item->value) && !is_null($x)) {
                    $y = $this->GridY($item->value, $axis);
                    if (!is_null($y)) {
                        $marker_id = $this->MarkerLabel($i, $bnum, $item, $x, $y);
                        $extra     = empty($marker_id) ? null : array('id' => $marker_id);
                        $this->AddMarker($x, $y, $item, $extra, $i);
                    }
                }
                ++$bnum;
            }
        }

        list($best_fit_above, $best_fit_below) = $this->BestFitLines();
        $body .= $best_fit_below;
        $body .= $this->OverShapes();
        $body .= $this->Axes();
        $body .= $this->CrossHairs();
        $body .= $this->DrawMarkers();
        $body .= $best_fit_above;

        return $body;
    }

    /**
     * Sets up values array.
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
     * Checks that the data produces a 2-D plot.
     */
    protected function CheckValues()
    {
        parent::CheckValues();

        // using force_assoc makes things work properly
        if ($this->values->AssociativeKeys()) {
            $this->force_assoc = true;
        }
    }
}
