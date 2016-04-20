<?php

namespace GGS\SVGGraph;

/**
 * ScatterGraph - points with axes and grid.
 */
class ScatterGraph extends PointGraph
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
        $this->ColourSetup($this->values->ItemsCount());

        $bnum = 0;
        foreach ($this->values[0] as $item) {
            $x = $this->GridPosition($item->key, $bnum);
            if (!is_null($item->value) && !is_null($x)) {
                $y = $this->GridY($item->value);
                if (!is_null($y)) {
                    $marker_id = $this->MarkerLabel(0, $bnum, $item, $x, $y);
                    $extra     = empty($marker_id) ? null : array('id' => $marker_id);
                    $this->AddMarker($x, $y, $item, $extra);
                }
            }
            ++$bnum;
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
