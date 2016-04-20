<?php

namespace GGS\SVGGraph;

/**
 * Base class for all graph types.
 *
 * @property string semantic_classes
 * @property string legend_show_empty
 * @property string show_tooltips
 * @property string pad_bottom
 * @property string empty_use
 * @property string data_label_min_space
 */
abstract class Graph
{
    protected $settings = array();
    /**
     * @var SVGGraphData|SVGGraphStructuredData
     */
    protected $values = array();
    protected $link_base = '';
    protected $link_target = '_blank';
    protected $links = array();

    protected $gradients = array();
    protected $gradient_map = array();
    protected $pattern_list = null;
    protected $defs = array();
    protected $back_matter = '';

    protected $namespaces = array();
    protected static $javascript = null;
    private static $last_id = 0;
    private static $precision = 5;
    private static $decimal = '.';
    private static $thousands = ',';
    protected $legend_reverse = false;
    protected $force_assoc = false;
    protected $repeated_keys = 'error';
    protected $require_structured = false;
    protected $require_integer_keys = true;
    protected $multi_graph = null;
    /**
     * @var SVGGraphColours
     */
    public $colours;

    public function __construct($w, $h, $settings = null)
    {
        $this->width  = $w;
        $this->height = $h;

        // get settings from ini file that are relevant to this class
        $ini_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'svggraph.ini';
        if (!file_exists($ini_file)) {
            $ini_settings = false;
        } else {
            $ini_settings = parse_ini_file($ini_file, true);
        }
        if ($ini_settings === false) {
            die("Ini file [{$ini_file}] not found -- exiting");
        }

        $reflect   = new \ReflectionClass($this);
        $class     = $reflect->getShortName();
        $hierarchy = array($class);
        while ($parent = $reflect->getParentClass()) {
            array_unshift($hierarchy, $parent->getShortName());
            $reflect = $parent;
        }

        while (count($hierarchy)) {
            $class = array_shift($hierarchy);
            if (array_key_exists($class, $ini_settings)) {
                $this->settings = array_merge($this->settings, $ini_settings[$class]);
            }
        }

        if (is_array($settings)) {
            $this->Settings($settings);
        }
    }

    /**
     * Retrieves properties from the settings array if they are not
     * already available as properties.
     *
     * @param $name
     *
     * @return null
     */
    public function __get($name)
    {
        $this->{$name} = isset($this->settings[$name]) ? $this->settings[$name] : null;

        return $this->{$name};
    }

    /**
     * Make empty($this->option) more robust.
     *
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->settings[$name]);
    }

    /**
     * Sets the options.
     *
     * @param $settings
     */
    public function Settings(&$settings)
    {
        foreach ($settings as $key => $value) {
            $this->settings[$key] = $value;
            $this->{$key}         = $value;
        }
    }

    /**
     * Sets the graph values.
     *
     * @param $values
     */
    public function Values($values)
    {
        $new_values = array();
        $v          = func_get_args();
        if (count($v) == 1) {
            $v = array_shift($v);
        }

        $set_values = true;
        if (is_array($v)) {
            reset($v);
            $first_key = key($v);
            if (!is_null($first_key) && is_array($v[$first_key])) {
                foreach ($v as $data_set) {
                    $new_values[] = $data_set;
                }
                $set_values = false;
            }
        }

        if ($set_values) {
            $new_values[] = $v;
        }

        if ($this->scatter_2d) {
            $this->scatter_2d = false;
            if (empty($this->structure)) {
                $this->structure = array('key' => 0, 'value' => 1, 'datasets' => true);
            }
        }

        if ($this->structured_data || is_array($this->structure)) {
            $this->structured_data = true;

            if (is_array($this->structure)) {
                $this->structure['_before']   = $this->units_before_x;
                $this->structure['_after']    = $this->units_x;
                $this->structure['_encoding'] = $this->encoding;
            }
            $this->values = new SVGGraphStructuredData(
                $new_values,
                $this->force_assoc, $this->structure, $this->repeated_keys,
                $this->require_integer_keys, $this->require_structured
            );
        } else {
            $this->values = new SVGGraphData($new_values, $this->force_assoc);
            if (!$this->values->error && !empty($this->require_structured)) {
                $this->values->error = get_class($this) . ' requires structured data';
            }
        }
    }

    /**
     * Sets the links from each item.
     */
    public function Links()
    {
        $this->links = func_get_args();
    }

    protected function GetMinValue()
    {
        if (!is_null($this->multi_graph)) {
            return $this->multi_graph->GetMinValue();
        }

        return $this->values->GetMinValue();
    }

    protected function GetMaxValue()
    {
        if (!is_null($this->multi_graph)) {
            return $this->multi_graph->GetMaxValue();
        }

        return $this->values->GetMaxValue();
    }

    protected function GetMinKey()
    {
        if (!is_null($this->multi_graph)) {
            return $this->multi_graph->GetMinKey();
        }

        return $this->values->GetMinKey();
    }

    protected function GetMaxKey()
    {
        if (!is_null($this->multi_graph)) {
            return $this->multi_graph->GetMaxKey();
        }

        return $this->values->GetMaxKey();
    }

    protected function GetKey($i)
    {
        if (!is_null($this->multi_graph)) {
            return $this->multi_graph->GetKey($i);
        }

        return $this->values->GetKey($i);
    }

    /**
     * Draws the selected graph.
     */
    public function DrawGraph()
    {
        $canvas_id = $this->NewID();

        $contents = $this->Canvas($canvas_id);
        $contents .= $this->DrawTitle();
        $contents .= $this->Draw();
        $contents .= $this->DrawDataLabels();
        $contents .= $this->DrawBackMatter();
        $contents .= $this->DrawLegend();

        // rounded rects might need a clip path
        if ($this->back_round && $this->back_round_clip) {
            $group = array('clip-path' => "url(#{$canvas_id})");

            return $this->Element('g', $group, null, $contents);
        }

        return $contents;
    }

    /**
     * Adds any markup that goes after the graph.
     */
    protected function DrawBackMatter()
    {
        return $this->back_matter;
    }

    /**
     * Draws the legend.
     */
    protected function DrawLegend()
    {
        if (empty($this->legend_entries)) {
            return '';
        }

        // need to find the actual number of entries in the legend
        $entry_count = 0;
        $longest     = 0;
        foreach ($this->legend_entries as $key => $value) {
            $entry = $this->DrawLegendEntry($key, 0, 0, 20, 20);
            if ($entry != '') {
                ++$entry_count;
                if (mb_strlen($value, $this->encoding) > $longest) {
                    $longest = mb_strlen($value, $this->encoding);
                }
            }
        }
        if (!$entry_count) {
            return '';
        }

        $title        = '';
        $title_width  = $entries_x = 0;
        $text_columns = $entry_columns = array();

        $start_y       = $this->legend_padding;
        $w             = $this->legend_entry_width;
        $x             = 0;
        $entry_height  = max($this->legend_font_size, $this->legend_entry_height);
        $text_y_offset = $entry_height / 2 + $this->legend_font_size / 2;

        // make room for title
        if ($this->legend_title != '') {
            $title_font        = $this->GetFirst(
                $this->legend_title_font,
                $this->legend_font
            );
            $title_font_size   = $this->GetFirst(
                $this->legend_title_font_size,
                $this->legend_font_size
            );
            $title_font_adjust = $this->GetFirst(
                $this->legend_title_font_adjust,
                $this->legend_font_adjust
            );
            $title_colour      = $this->GetFirst(
                $this->legend_title_colour,
                $this->legend_colour
            );

            $start_y += $title_font_size + $this->legend_padding;
            $title_width = $this->legend_padding * 2 +
                           $title_font_size * $title_font_adjust *
                           mb_strlen($this->legend_title, $this->encoding);
        }

        $columns    = max(1, min(ceil($this->legend_columns), $entry_count));
        $per_column = ceil($entry_count / $columns);
        $columns    = ceil($entry_count / $per_column);
        $column     = 0;

        $text           = array('x' => 0);
        $legend_entries = $this->legend_reverse ?
            array_reverse($this->legend_entries, true) : $this->legend_entries;

        $column_entry = 0;
        $y            = $start_y;
        foreach ($legend_entries as $key => $value) {
            if (!empty($value)) {
                $entry = $this->DrawLegendEntry($key, $x, $y, $w, $entry_height);
                if (!empty($entry)) {
                    $text['y'] = $y + $text_y_offset;
                    if (isset($text_columns[$column])) {
                        $text_columns[$column] .= $this->Element('text', $text, null, $value);
                    } else {
                        $text_columns[$column] = $this->Element('text', $text, null, $value);
                    }
                    if (isset($entry_columns[$column])) {
                        $entry_columns[$column] .= $entry;
                    } else {
                        $entry_columns[$column] = $entry;
                    }
                    $y += $entry_height + $this->legend_padding;

                    if (++$column_entry == $per_column) {
                        $column_entry = 0;
                        $y            = $start_y;
                        ++$column;
                    }
                }
            }
        }
        // if there's nothing to go in the legend, stop now
        if (empty($entry_columns)) {
            return '';
        }

        $text_space = $longest * $this->legend_font_size *
                      $this->legend_font_adjust;
        if ($this->legend_text_side == 'left') {
            $text_x_offset    = $text_space + $this->legend_padding;
            $entries_x_offset = $text_space + $this->legend_padding * 2;
        } else {
            $text_x_offset    = $w + $this->legend_padding * 2;
            $entries_x_offset = $this->legend_padding;
        }
        $longest_width = $this->legend_padding * (2 * $columns + 1) +
                         ($this->legend_entry_width + $text_space) * $columns;
        $column_width  = $this->legend_padding * 2 + $this->legend_entry_width +
                         $text_space;
        $width         = max($title_width, $longest_width);
        $height        = $start_y + $per_column * ($entry_height + $this->legend_padding);

        // centre the entries if the title makes the box bigger
        if ($width > $longest_width) {
            $offset = ($width - $longest_width) / 2;
            $entries_x_offset += $offset;
            $text_x_offset += $offset;
        }

        $text_group = array('transform' => "translate($text_x_offset,0)");
        if ($this->legend_text_side == 'left') {
            $text_group['text-anchor'] = 'end';
        }
        $entries_group = array('transform' => "translate($entries_x_offset,0)");

        $parts = '';
        foreach ($entry_columns as $col) {
            $parts .= $this->Element('g', $entries_group, null, $col);
            $entries_x_offset += $column_width;
            $entries_group['transform'] = "translate($entries_x_offset,0)";
        }
        foreach ($text_columns as $col) {
            $parts .= $this->Element('g', $text_group, null, $col);
            $text_x_offset += $column_width;
            $text_group['transform'] = "translate($text_x_offset,0)";
        }

        // create box and title
        $box = array(
            'fill'   => $this->ParseColour($this->legend_back_colour),
            'width'  => $width,
            'height' => $height,
        );
        if ($this->legend_round > 0) {
            $box['rx'] = $box['ry'] = $this->legend_round;
        }
        if ($this->legend_stroke_width) {
            $box['stroke-width'] = $this->legend_stroke_width;
            $box['stroke']       = $this->legend_stroke_colour;
        }
        $rect = $this->Element('rect', $box);
        if ($this->legend_title != '') {
            $text['x']           = $width / 2;
            $text['y']           = $this->legend_padding + $title_font_size;
            $text['text-anchor'] = 'middle';
            if ($title_font != $this->legend_font) {
                $text['font-family'] = $title_font;
            }
            if ($title_font_size != $this->legend_font_size) {
                $text['font-size'] = $title_font_size;
            }
            if ($this->legend_title_font_weight != $this->legend_font_weight) {
                $text['font-weight'] = $this->legend_title_font_weight;
            }
            if ($title_colour != $this->legend_colour) {
                $text['fill'] = $title_colour;
            }
            $title = $this->Element('text', $text, null, $this->legend_title);
        }

        // create group to contain whole legend
        list($left, $top) = $this->ParsePosition(
            $this->legend_position,
            $width,
            $height
        );

        $group = array(
            'font-family' => $this->legend_font,
            'font-size'   => $this->legend_font_size,
            'fill'        => $this->legend_colour,
            'transform'   => "translate($left,$top)",
        );
        if ($this->legend_font_weight != 'normal') {
            $group['font-weight'] = $this->legend_font_weight;
        }

        // add shadow if not completely transparent
        if ($this->legend_shadow_opacity > 0) {
            $box['x']       = $box['y'] = 2 + ($this->legend_stroke_width / 2);
            $box['fill']    = '#000';
            $box['opacity'] = $this->legend_shadow_opacity;
            unset($box['stroke'], $box['stroke-width']);
            $rect = $this->Element('rect', $box) . $rect;
        }

        if ($this->legend_autohide) {
            $this->AutoHide($group);
        }
        if ($this->legend_draggable) {
            $this->SetDraggable($group);
        }

        return $this->Element('g', $group, null, $rect . $title . $parts);
    }

    /**
     * Parses a position string, returning x and y coordinates.
     *
     * @param     $pos
     * @param int $w
     * @param int $h
     * @param int $pad
     *
     * @return array
     */
    protected function ParsePosition($pos, $w = 0, $h = 0, $pad = 0)
    {
        $inner = true;
        $parts = preg_split('/\s+/', $pos);
        if (count($parts)) {
            // if 'outer' is found after 'inner', it takes precedence
            $parts    = array_reverse($parts);
            $inner_at = array_search('inner', $parts);
            $outer_at = array_search('outer', $parts);

            if ($outer_at !== false && ($inner_at === false || $inner_at < $outer_at)) {
                $inner = false;
            }
        }

        if ($inner) {
            $t = $this->pad_top;
            $l = $this->pad_left;
            $b = $this->height - $this->pad_bottom;
            $r = $this->width - $this->pad_right;
            // make sure it fits to keep RelativePosition happy
            if ($w > $r - $l) {
                $w = $r - $l;
            }
            if ($h > $b - $t) {
                $h = $b - $t;
            }
        } else {
            $t = $l = 0;
            $b = $this->height;
            $r = $this->width;
        }

        // ParsePosition is always inside canvas or graph, defaulted to top left
        $pos = 'top left ' . str_replace('outer', 'inner', $pos);

        return self::RelativePosition($pos, $t, $l, $b, $r, $w, $h, $pad);
    }

    /**
     * Returns [hpos,vpos,offset_x,offset_y] positions derived from full
     * position string.
     *
     * @param $pos
     *
     * @return array
     */
    public static function TranslatePosition($pos)
    {
        $parts    = preg_split('/\s+/', strtolower($pos));
        $offset_x = $offset_y = 0;
        $inside   = true;
        $vpos     = 'm';
        $hpos     = 'c';

        // translated positions:
        // ot, t, m, b, ob = outside top, top, middle, bottom, outside bottom
        // ol, l, c, r, or = outside left, left, centre, right, outside right
        while (count($parts)) {
            $part = array_shift($parts);
            switch ($part) {
                case 'outer' :
                case 'outside' :
                    $inside = false;
                    break;
                case 'inner' :
                case 'inside' :
                    $inside = true;
                    break;
                case 'top' :
                    $vpos = $inside ? 't' : 'ot';
                    break;
                case 'bottom' :
                    $vpos = $inside ? 'b' : 'ob';
                    break;
                case 'left' :
                    $hpos = $inside ? 'l' : 'ol';
                    break;
                case 'right' :
                    $hpos = $inside ? 'r' : 'or';
                    break;
                case 'above' :
                    $inside = false;
                    $vpos   = 'ot';
                    break;
                case 'below' :
                    $inside = false;
                    $vpos   = 'ob';
                    break;
                default:
                    if (is_numeric($part)) {
                        $offset_x = $part;
                        if (count($parts) && is_numeric($parts[0])) {
                            $offset_y = array_shift($parts);
                        }
                    }
            }
        }

        return array($hpos, $vpos, $offset_x, $offset_y);
    }

    /**
     * Returns [x,y,text-anchor,hpos,vpos] position that is $pos relative to the
     * top, left, bottom and right.
     * When $text is true, x and y are adjusted for text-anchor position.
     *
     * @param      $pos
     * @param      $top
     * @param      $left
     * @param      $bottom
     * @param      $right
     * @param      $width
     * @param      $height
     * @param      $pad
     * @param bool $text
     *
     * @return array
     */
    public static function RelativePosition(
        $pos,
        $top,
        $left,
        $bottom,
        $right,
        $width,
        $height,
        $pad,
        $text = false
    ) {
        list($hpos, $vpos, $offset_x, $offset_y) = self::TranslatePosition($pos);

        // if the containers have no thickness, position outside
        $translate = array('l' => 'ol', 'r' => 'or', 't' => 'ot', 'b' => 'ob');
        if ($top == $bottom && isset($translate[$vpos])) {
            $vpos = $translate[$vpos];
        }
        if ($left == $right && isset($translate[$hpos])) {
            $hpos = $translate[$hpos];
        }

        switch ($vpos) {
            case 'ot' :
                $y = $top - $height - $pad;
                break;
            case 't' :
                $y = $top + $pad;
                break;
            case 'b' :
                $y = $bottom - $height - $pad;
                break;
            case 'ob' :
                $y = $bottom + $pad;
                break;
            case 'm' :
            default :
                $y = $top + ($bottom - $top - $height) / 2;
                break;
        }

        if (($hpos == 'r' || $hpos == 'l') && $right - $left - $pad - $width < 0) {
            $hpos = 'c';
        }
        switch ($hpos) {
            case 'ol' :
                $x = $left - $width - $pad;
                break;
            case 'l' :
                $x = $left + $pad;
                break;
            case 'r' :
                $x = $right - $width - $pad;
                break;
            case 'or' :
                $x = $right + $pad;
                break;
            case 'c' :
            default :
                $x = $left + ($right - $left - $width) / 2;
                break;
        }

        $y += $offset_y;
        $x += $offset_x;

        // third return value is text alignment
        $align_map  = array(
            'ol' => 'end',
            'l'  => 'start',
            'c'  => 'middle',
            'r'  => 'end',
            'or' => 'start',
        );
        $text_align = $align_map[$hpos];

        // in text mode, adjust X for text alignment
        if ($text && $hpos != 'l' && $hpos != 'or') {
            if ($hpos == 'c') {
                $x += $width / 2;
            } else {
                $x += $width;
            }
        }

        return array($x, $y, $text_align, $hpos, $vpos);
    }

    /**
     * Subclasses must draw the entry, if they can.
     *
     * @param $key
     * @param $x
     * @param $y
     * @param $w
     * @param $h
     *
     * @return string
     */
    protected function DrawLegendEntry($key, $x, $y, $w, $h)
    {
        return '';
    }

    /**
     * Draws the graph title, if there is one.
     */
    protected function DrawTitle()
    {
        // graph_title is available for all graph types
        if (mb_strlen($this->graph_title, $this->encoding) <= 0) {
            return '';
        }

        $pos         = $this->graph_title_position;
        $text        = array(
            'font-size'   => $this->graph_title_font_size,
            'font-family' => $this->graph_title_font,
            'font-weight' => $this->graph_title_font_weight,
            'text-anchor' => 'middle',
            'fill'        => $this->graph_title_colour,
        );
        $lines       = $this->CountLines($this->graph_title);
        $title_space = $this->graph_title_font_size * $lines +
                       $this->graph_title_space;
        if ($pos != 'top' && $pos != 'bottom' && $pos != 'left' && $pos != 'right') {
            $pos = 'top';
        }
        $pad_side = 'pad_' . $pos;

        // ensure outside padding is at least the title space
        if ($this->{$pad_side} < $this->graph_title_space) {
            $this->{$pad_side} = $this->graph_title_space;
        }

        if ($pos == 'left') {
            $text['x']         = $this->pad_left + $this->graph_title_font_size;
            $text['y']         = $this->height / 2;
            $text['transform'] = "rotate(270,$text[x],$text[y])";
        } elseif ($pos == 'right') {
            $text['x']         = $this->width - $this->pad_right -
                                 $this->graph_title_font_size;
            $text['y']         = $this->height / 2;
            $text['transform'] = "rotate(90,$text[x],$text[y])";
        } elseif ($pos == 'bottom') {
            $text['x'] = $this->width / 2;
            $text['y'] = $this->height - $this->pad_bottom -
                         $this->graph_title_font_size * ($lines - 1);
        } else {
            $text['x'] = $this->width / 2;
            $text['y'] = $this->pad_top + $this->graph_title_font_size;
        }
        // increase padding by size of text
        $this->{$pad_side} += $title_space;

        // the Text function will break it into lines
        return $this->Text(
            $this->graph_title,
            $this->graph_title_font_size,
            $text
        );
    }

    /**
     * This should be overridden by subclass!
     */
    abstract protected function Draw();

    /**
     * Displays the background image.
     */
    protected function BackgroundImage()
    {
        if (!$this->back_image) {
            return '';
        }
        $image = array(
            'width'               => $this->back_image_width,
            'height'              => $this->back_image_height,
            'x'                   => $this->back_image_left,
            'y'                   => $this->back_image_top,
            'xlink:href'          => $this->back_image,
            'preserveAspectRatio' => ($this->back_image_mode == 'stretch' ? 'none' : 'xMinYMin'),
        );
        $style = array();
        if ($this->back_image_opacity) {
            $style['opacity'] = $this->back_image_opacity;
        }

        $contents = '';
        if ($this->back_image_mode == 'tile') {
            $image['x'] = 0;
            $image['y'] = 0;
            $im         = $this->Element('image', $image, $style);
            $pattern    = array(
                'id'           => $this->NewID(),
                'width'        => $this->back_image_width,
                'height'       => $this->back_image_height,
                'x'            => $this->back_image_left,
                'y'            => $this->back_image_top,
                'patternUnits' => 'userSpaceOnUse',
            );
            // tiled image becomes a pattern to replace background colour
            $this->defs[]      = $this->Element('pattern', $pattern, null, $im);
            $this->back_colour = "url(#{$pattern['id']})";
        } else {
            $im = $this->Element('image', $image, $style);
            $contents .= $im;
        }

        return $contents;
    }

    /**
     * Displays the background.
     *
     * @param $id
     *
     * @return string
     * @throws \Exception
     */
    protected function Canvas($id)
    {
        $bg      = $this->BackgroundImage();
        $colour  = $this->ParseColour($this->back_colour);
        $opacity = 1;
        if (strpos($colour, ':') !== false) {
            list($colour, $opacity) = explode(':', $colour);
        }

        $canvas = array(
            'width'        => '100%',
            'height'       => '100%',
            'fill'         => $colour,
            'stroke-width' => 0,
        );
        if ($opacity < 1) {
            if ($opacity <= 0) {
                $canvas['fill'] = 'none';
            } else {
                $canvas['opacity'] = $opacity;
            }
        }

        if ($this->back_round) {
            $canvas['rx'] = $canvas['ry'] = $this->back_round;
        }
        if ($bg == '' && $this->back_stroke_width) {
            $canvas['stroke-width'] = $this->back_stroke_width;
            $canvas['stroke']       = $this->back_stroke_colour;
        }
        $c_el = $this->Element('rect', $canvas);

        // create a clip path for rounded rectangle
        if ($this->back_round) {
            $this->defs[] = $this->Element(
                'clipPath',
                array('id' => $id),
                null,
                $c_el
            );
        }
        // if the background image is an element, insert it between the background
        // colour and border rect
        if ($bg != '') {
            $c_el .= $bg;
            if ($this->back_stroke_width) {
                $canvas['stroke-width'] = $this->back_stroke_width;
                $canvas['stroke']       = $this->back_stroke_colour;
                $canvas['fill']         = 'none';
                $c_el .= $this->Element('rect', $canvas);
            }
        }

        return $c_el;
    }

    /**
     * Fits text to a box - text will be bottom-aligned.
     *
     * @param      $text
     * @param      $x
     * @param      $y
     * @param      $w
     * @param      $h
     * @param null $attribs
     * @param null $styles
     *
     * @return string
     */
    protected function TextFit(
        $text,
        $x,
        $y,
        $w,
        $h,
        $attribs = null,
        $styles = null
    ) {
        $pos = array('onload' => "textFit(evt,$x,$y,$w,$h)");
        if (is_array($attribs)) {
            $pos = array_merge($attribs, $pos);
        }
        $txt = $this->Element('text', $pos, $styles, $text);

        /* Uncomment to see the box
        $rect = array('x' => $x, 'y' => $y, 'width' => $w, 'height' => $h,
          'fill' => 'none', 'stroke' => 'black');
        $txt .= $this->Element('rect', $rect);
        **/
        $this->AddFunction('textFit');

        return $txt;
    }

    /**
     * Returns a text element, with tspans for multiple lines.
     *
     * @param      $text
     * @param      $line_spacing
     * @param      $attribs
     * @param null $styles
     *
     * @return string
     */
    public function Text($text, $line_spacing, $attribs, $styles = null)
    {
        if (strpos($text, "\n") === false) {
            $content = ($text == ''
                ? ' '
                : htmlspecialchars(
                    $text,
                    ENT_COMPAT,
                    $this->encoding
                ));
        } else {
            $lines   = explode("\n", $text);
            $content = '';
            $tspan   = array('x' => $attribs['x'], 'dy' => 0);
            foreach ($lines as $line) {
                // blank tspan elements collapse to nothing, so insert a space
                $line = ($line == ''
                    ? ' '
                    : htmlspecialchars(
                        $line,
                        ENT_COMPAT,
                        $this->encoding
                    ));

                $content .= $this->Element('tspan', $tspan, null, $line);
                $tspan['dy'] = $line_spacing;
            }
        }

        return $this->Element('text', $attribs, $styles, $content);
    }

    /**
     * Returns [width,height] of text
     * $text = string OR text length.
     *
     * @param     $text
     * @param     $font_size
     * @param     $font_adjust
     * @param     $encoding
     * @param int $angle
     * @param int $line_spacing
     *
     * @return array
     */
    public static function TextSize(
        $text,
        $font_size,
        $font_adjust,
        $encoding,
        $angle = 0,
        $line_spacing = 0
    ) {
        $height = $font_size;
        if (is_int($text)) {
            $len = $text;
        } else {
            // replace all entities with an underscore
            $text = preg_replace('/&[^;]+;/', '_', $text);
            if ($line_spacing > 0) {
                $len   = 0;
                $lines = explode("\n", $text);
                foreach ($lines as $l) {
                    if (mb_strlen($l, $encoding) > $len) {
                        $len = mb_strlen($l, $encoding);
                    }
                }
                $height += $line_spacing * (count($lines) - 1);
            } else {
                $len = mb_strlen($text, $encoding);
            }
        }
        $width = $len * $font_size * $font_adjust;
        if ($angle % 180 != 0) {
            if ($angle % 90 == 0) {
                $w      = $height;
                $height = $width;
                $width  = $w;
            } else {
                $a      = deg2rad($angle);
                $sa     = abs(sin($a));
                $ca     = abs(cos($a));
                $w      = $ca * $width + $sa * $height;
                $h      = $sa * $width + $ca * $height;
                $width  = $w;
                $height = $h;
            }
        }

        return array($width, $height);
    }

    /**
     * Returns the number of lines in a string.
     *
     * @param $text
     *
     * @return int
     */
    public static function CountLines($text)
    {
        $c   = 1;
        $pos = 0;
        while (($pos = strpos($text, "\n", $pos)) !== false) {
            ++$c;
            ++$pos;
        }

        return $c;
    }

    /**
     * Displays readable (hopefully) error message.
     *
     * @param $error
     *
     * @return string
     */
    protected function ErrorText($error)
    {
        $text  = array('x' => $this->pad_left, 'y' => $this->height - 3);
        $style = array(
            'font-family' => 'monospace',
            'font-size'   => '11px',
            'font-weight' => 'bold',
        );

        $e = $this->ContrastText(
            $text['x'],
            $text['y'],
            $error,
            'blue',
            'white',
            $style
        );

        return $e;
    }

    /**
     * Displays high-contrast text.
     *
     * @param        $x
     * @param        $y
     * @param        $text
     * @param string $fcolour
     * @param string $bcolour
     * @param null   $properties
     * @param null   $styles
     *
     * @return string
     */
    protected function ContrastText(
        $x,
        $y,
        $text,
        $fcolour = 'black',
        $bcolour = 'white',
        $properties = null,
        $styles = null
    ) {
        $props = array(
            'transform' => 'translate(' . $x . ',' . $y . ')',
            'fill'      => $fcolour,
        );
        if (is_array($properties)) {
            $props = array_merge($properties, $props);
        }

        $bg = $this->Element(
            'text',
            array('stroke-width' => '2px', 'stroke' => $bcolour),
            null,
            $text
        );
        $fg = $this->Element('text', null, null, $text);

        return $this->Element('g', $props, $styles, $bg . $fg);
    }

    /**
     * Builds an element.
     *
     * @param      $name
     * @param null $attribs
     * @param null $styles
     * @param null $content
     *
     * @return string
     */
    public function Element(
        $name,
        $attribs = null,
        $styles = null,
        $content = null
    ) {
        // these properties require units to work well
        $require_units = array(
            'stroke-width'      => 1,
            'stroke-dashoffset' => 1,
            'font-size'         => 1,
            'baseline-shift'    => 1,
            'kerning'           => 1,
            'letter-spacing'    => 1,
            'word-spacing'      => 1,
        );

        if ($this->namespace && strpos($name, ':') === false) {
            $name = 'svg:' . $name;
        }
        $element = '<' . $name;
        if (is_array($attribs)) {
            foreach ($attribs as $attr => $val) {

                // if units required, add px
                if (is_numeric($val)) {
                    if (isset($require_units[$attr])) {
                        $val .= 'px';
                    }
                } else {
                    $val = htmlspecialchars($val, ENT_COMPAT, $this->encoding);
                }
                $element .= ' ' . $attr . '="' . $val . '"';
            }
        }

        if (is_array($styles)) {
            $element .= ' style="';
            foreach ($styles as $attr => $val) {
                // check units again
                if (is_numeric($val)) {
                    if (isset($require_units[$attr])) {
                        $val .= 'px';
                    }
                } else {
                    $val = htmlspecialchars($val, ENT_COMPAT, $this->encoding);
                }
                $element .= $attr . ':' . $val . ';';
            }
            $element .= '"';
        }

        if (is_null($content)) {
            $element .= "/>\n";
        } else {
            $element .= '>' . $content . '</' . $name . ">\n";
        }

        return $element;
    }

    /**
     * Returns a link URL or NULL if none.
     *
     * @param     $item
     * @param     $key
     * @param int $row
     *
     * @return null|string
     */
    protected function GetLinkURL($item, $key, $row = 0)
    {
        $link = is_null($item) ? null : $item->Data('link');
        if (is_null($link) && is_array($this->links[$row]) &&
            isset($this->links[$row][$key])
        ) {
            $link = $this->links[$row][$key];
        }

        // check for absolute links
        if (!is_null($link) && strpos($link, '//') === false) {
            $link = $this->link_base . $link;
        }

        return $link;
    }

    /**
     * Retrieves a link.
     *
     * @param     $item
     * @param     $key
     * @param     $content
     * @param int $row
     *
     * @return string
     */
    protected function GetLink($item, $key, $content, $row = 0)
    {
        $link = $this->GetLinkURL($item, $key, $row);
        if (is_null($link)) {
            return $content;
        }

        $link_attr = array('xlink:href' => $link, 'target' => $this->link_target);

        return $this->Element('a', $link_attr, null, $content);
    }

    /**
     * Sets up the colour class.
     *
     * @param      $count
     * @param null $datasets
     */
    protected function ColourSetup($count, $datasets = null)
    {
        $this->colours->Setup($count, $datasets);
    }

    /**
     * Returns a colour reference.
     *
     * @param      $item
     * @param      $key
     * @param null $dataset
     * @param bool $no_gradient
     * @param bool $allow_pattern
     *
     * @return string
     * @throws \Exception
     */
    protected function GetColour(
        $item,
        $key,
        $dataset = null,
        $no_gradient = false,
        $allow_pattern = false
    ) {
        $colour  = 'none';
        $icolour = is_null($item) ? null : $item->Data('colour');
        if (!is_null($icolour)) {
            $colour = $icolour;
            $key    = null; // don't reuse existing colours
        } else {
            $c = $this->colours->GetColour($key, $dataset);
            if (!is_null($c)) {
                $colour = $c;
            }

            // make key reflect dataset as well (for gradients)
            if (!is_null($dataset)) {
                $key = "{$dataset}:{$key}";
            }
        }

        return $this->ParseColour($colour, $key, $no_gradient, $allow_pattern);
    }

    /**
     * Converts a SVGGraph colour/gradient/pattern to a SVG attribute.
     *
     * @param      $colour
     * @param null $key
     * @param bool $no_gradient
     * @param bool $allow_pattern
     * @param bool $radial_gradient
     *
     * @return string
     * @throws \Exception
     */
    public function ParseColour(
        $colour,
        $key = null,
        $no_gradient = false,
        $allow_pattern = false,
        $radial_gradient = false
    ) {
        if (is_array($colour)) {
            if (!isset($colour['pattern'])) {
                $allow_pattern = false;
            }
            if (count($colour) < 2 || ($no_gradient && !$allow_pattern)) {
                $colour = $this->SolidColour($colour);
            } elseif (isset($colour['pattern'])) {
                $pattern_id = $this->AddPattern($colour);
                $colour     = "url(#{$pattern_id})";
            } else {
                $err = array_diff_key($colour, array_keys(array_keys($colour)));
                if ($err) {
                    throw new \Exception('Malformed gradient/pattern');
                }
                $gradient_id = $this->AddGradient($colour, $key, $radial_gradient);
                $colour      = "url(#{$gradient_id})";
            }
        }

        return $colour;
    }

    /**
     * Returns the solid colour from a gradient.
     *
     * @param $c
     *
     * @return string
     */
    protected static function SolidColour($c)
    {
        if (is_array($c)) {
            // grab the first colour in the array, discarding opacity
            $c     = $c[0];
            $colon = strpos($c, ':');
            if ($colon) {
                $c = substr($c, 0, $colon);
            }
        }

        return $c;
    }

    /**
     * Returns the first non-empty argument.
     */
    protected static function GetFirst()
    {
        $opts = func_get_args();
        foreach ($opts as $opt) {
            if (!empty($opt) || $opt === 0) {
                return $opt;
            }
        }
    }

    /**
     * Returns an option from array, or non-array option.
     *
     * @param $o
     * @param $i
     *
     * @return
     */
    protected static function ArrayOption($o, $i)
    {
        return is_array($o) ? $o[$i % count($o)] : $o;
    }

    /**
     * Checks that the data are valid.
     */
    protected function CheckValues()
    {
        if ($this->values->error) {
            throw new \Exception($this->values->error);
        }
    }

    /**
     * Sets the stroke options for an element.
     *
     * @param      $attr
     * @param      $item
     * @param int  $set
     * @param null $line_join
     */
    protected function SetStroke(&$attr, &$item, $set = 0, $line_join = null)
    {
        $stroke_width = $this->GetFromItemOrMember('stroke_width', $set, $item);
        if ($stroke_width > 0) {
            $attr['stroke']       = $this->GetFromItemOrMember('stroke_colour', $set, $item);
            $attr['stroke-width'] = $stroke_width;
            if (!is_null($line_join)) {
                $attr['stroke-linejoin'] = $line_join;
            } else {
                unset($attr['stroke-linejoin']);
            }

            $dash = $this->GetFromItemOrMember('stroke_dash', $set, $item);
            if (!empty($dash)) {
                $attr['stroke-dasharray'] = $dash;
            } else {
                unset($attr['stroke-dasharray']);
            }
        }
    }

    /**
     * Creates a new ID for an element.
     */
    public function NewID()
    {
        return $this->id_prefix . 'e' . base_convert(++self::$last_id, 10, 36);
    }

    /**
     * Adds markup to be inserted between graph and legend.
     *
     * @param $fragment
     */
    public function AddBackMatter($fragment)
    {
        $this->back_matter .= $fragment;
    }

    /**
     * Loads the Javascript class.
     */
    private function LoadJavascript()
    {
        if (!isset(self::$javascript)) {
            self::$javascript = new SVGGraphJavascript($this->settings, $this);
        }
    }

    /**
     * Adds one or more javascript functions.
     *
     * @param $name
     */
    protected function AddFunction($name)
    {
        $this->LoadJavascript();
        $fns = func_get_args();
        foreach ($fns as $fn) {
            self::$javascript->AddFunction($fn);
        }
    }

    /**
     * Adds a Javascript variable
     * - use $value:$more for assoc
     * - use null:$more for array.
     *
     * @param      $var
     * @param      $value
     * @param null $more
     * @param bool $quote
     */
    public function InsertVariable($var, $value, $more = null, $quote = true)
    {
        $this->LoadJavascript();
        self::$javascript->InsertVariable($var, $value, $more, $quote);
    }

    /**
     * Insert a comment into the Javascript section - handy for debugging!
     *
     * @param $details
     */
    public function InsertComment($details)
    {
        $this->LoadJavascript();
        self::$javascript->InsertComment($details);
    }

    /**
     * Adds a pattern, returning the element ID.
     *
     * @param $pattern
     *
     * @return
     * @throws \Exception
     */
    public function AddPattern($pattern)
    {
        if (is_null($this->pattern_list)) {
            $this->pattern_list = new SVGGraphPatternList($this);
        }

        return $this->pattern_list->Add($pattern);
    }

    /**
     * Adds a gradient to the list, returning the element ID for use in url.
     *
     * @param      $colours
     * @param null $key
     * @param bool $radial
     *
     * @return string
     */
    public function AddGradient($colours, $key = null, $radial = false)
    {
        if (is_null($key) || !isset($this->gradients[$key])) {
            if ($radial) {
                // if this is a radial gradient, it must end with 'r'
                $last = count($colours) - 1;
                if (strlen($colours[$last]) == 1) {
                    $colours[$last] = 'r';
                } else {
                    $colours[] = 'r';
                }
            }

            // find out if this gradient already stored
            $hash = serialize($colours);
            if (isset($this->gradient_map[$hash])) {
                return $this->gradient_map[$hash];
            }

            $id = $this->NewID();
            if (is_null($key)) {
                $key = $id;
            }
            $this->gradients[$key]     = array(
                'id'      => $id,
                'colours' => $colours,
            );
            $this->gradient_map[$hash] = $id;

            return $id;
        }

        return $this->gradients[$key]['id'];
    }

    /**
     * Creates a linear gradient element.
     *
     * @param $key
     *
     * @return string
     */
    private function MakeLinearGradient($key)
    {
        $stops     = '';
        $direction = 'v';
        $type      = 'linearGradient';
        $colours   = $this->gradients[$key]['colours'];
        $id        = $this->gradients[$key]['id'];

        if (in_array($colours[count($colours) - 1], array('h', 'v', 'r'))) {
            $direction = array_pop($colours);
        }
        if ($direction == 'r') {
            $type     = 'radialGradient';
            $gradient = array('id' => $id);
        } else {
            $x2       = $direction == 'v' ? 0 : '100%';
            $y2       = $direction == 'h' ? 0 : '100%';
            $gradient = array(
                'id' => $id,
                'x1' => 0,
                'x2' => $x2,
                'y1' => 0,
                'y2' => $y2,
            );
        }

        $col_mul = 100 / (count($colours) - 1);
        $offset  = 0;
        foreach ($colours as $pos => $colour) {
            $opacity = null;
            $poffset = $pos * $col_mul;
            if (strpos($colour, ':') !== false) {
                // opacity, stop offset or both
                $parts = explode(':', $colour);
                if (is_numeric($parts[0])) {
                    $poffset = array_shift($parts);
                }
                $colour  = array_shift($parts);
                $opacity = array_shift($parts); // NULL if not set
            }
            // set the offset to the most meaningful number
            $offset = min(100, max(0, $offset, $poffset));
            $stop   = array(
                'offset'     => $offset . '%',
                'stop-color' => $colour,
            );
            if (is_numeric($opacity)) {
                $stop['stop-opacity'] = $opacity;
            }
            $stops .= $this->Element('stop', $stop);
        }

        return $this->Element($type, $gradient, null, $stops);
    }

    /**
     * Adds an inline event handler to an element's array.
     *
     * @param $array
     * @param $evt
     * @param $code
     */
    protected function AddEventHandler(&$array, $evt, $code)
    {
        $this->LoadJavascript();
        self::$javascript->AddEventHandler($array, $evt, $code);
    }

    /**
     * Makes an item draggable.
     *
     * @param $element
     */
    protected function SetDraggable(&$element)
    {
        $this->LoadJavascript();
        self::$javascript->SetDraggable($element);
    }

    /**
     * Makes something auto-hide.
     *
     * @param $element
     */
    protected function AutoHide(&$element)
    {
        $this->LoadJavascript();
        self::$javascript->AutoHide($element);
    }

    /**
     * Default tooltip contents are key and value, or whatever
     * $key is if $value is not set.
     *
     * @param      $element
     * @param      $item
     * @param      $dataset
     * @param      $key
     * @param null $value
     * @param bool $duplicate
     */
    protected function SetTooltip(
        &$element,
        &$item,
        $dataset,
        $key,
        $value = null,
        $duplicate = false
    ) {
        if (is_callable($this->tooltip_callback)) {
            if (is_null($value)) {
                $value = $key;
            }
            $text = call_user_func($this->tooltip_callback, $dataset, $key, $value);
        } elseif (is_array($this->structure) && isset($this->structure['tooltip'])) {
            // use structured data tooltips if specified
            $text = $item->Data('tooltip');
        } else {
            $text = $this->FormatTooltip($item, $dataset, $key, $value);
        }
        if (is_null($text)) {
            return;
        }
        $text = addslashes(str_replace("\n", '\n', $text));
        self::$javascript->SetTooltip($element, $text, $duplicate);
    }

    /**
     * Default format is value only.
     *
     * @param $item
     * @param $dataset
     * @param $key
     * @param $value
     *
     * @return string
     */
    protected function FormatTooltip(&$item, $dataset, $key, $value)
    {
        return $this->units_before_tooltip . self::NumString($value) .
               $this->units_tooltip;
    }

    /**
     * Sets the fader for an element.
     *
     * @param array  &$element  Element that should cause fading
     * @param number $in        Fade in speed
     * @param number $out       Fade out speed
     * @param null   $target
     * @param bool   $duplicate TRUE to create transparent overlay
     *
     * @internal param string $id ID of element to be faded
     */
    protected function SetFader(
        &$element,
        $in,
        $out,
        $target = null,
        $duplicate = false
    ) {
        $this->LoadJavascript();
        self::$javascript->SetFader($element, $in, $out, $target, $duplicate);
    }

    /**
     * Sets click visibility for $target when $element is clicked.
     *
     * @param      $element
     * @param      $target
     * @param      $hidden
     * @param bool $duplicate
     */
    protected function SetClickShow(
        &$element,
        $target,
        $hidden,
        $duplicate = false
    ) {
        $this->LoadJavascript();
        self::$javascript->SetClickShow($element, $target, $hidden, $duplicate);
    }

    public function SetPopFront(&$element, $target, $duplicate = false)
    {
        $this->LoadJavascript();
        self::$javascript->SetPopFront($element, $target, $duplicate);
    }

    /**
     * Add an overlaid copy of an element, with opacity of 0
     * $from and $to are the IDs of the source and destination.
     *
     * @param $from
     * @param $to
     */
    protected function AddOverlay($from, $to)
    {
        $this->LoadJavascript();
        self::$javascript->AddOverlay($from, $to);
    }

    /**
     * Adds a data label to the list.
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
        if (!$this->ArrayOption($this->show_data_labels, $dataset)) {
            return false;
        }
        if (!isset($this->data_labels)) {
            $this->data_labels = new DataLabels($this);
        }

        // set up fading for this label?
        $id       = null;
        $fade_in  = $this->ArrayOption($this->data_label_fade_in_speed, $dataset);
        $fade_out = $this->ArrayOption($this->data_label_fade_out_speed, $dataset);
        $click    = $this->ArrayOption($this->data_label_click, $dataset);
        $popup    = $this->ArrayOption($this->data_label_popfront, $dataset);
        if ($click == 'hide' || $click == 'show') {
            $id = $this->NewID();
            $this->SetClickShow(
                $element,
                $id,
                $click == 'hide',
                $duplicate && !$this->compat_events
            );
        }
        if ($popup) {
            if (!$id) {
                $id = $this->NewID();
            }
            $this->SetPopFront($element, $id, $duplicate && !$this->compat_events);
        }
        if ($fade_in || $fade_out) {
            $speed_in  = $fade_in ? $fade_in / 100 : 0;
            $speed_out = $fade_out ? $fade_out / 100 : 0;
            if (!$id) {
                $id = $this->NewID();
            }
            $this->SetFader(
                $element,
                $speed_in,
                $speed_out,
                $id,
                $duplicate && !$this->compat_events
            );
        }
        $this->data_labels->AddLabel(
            $dataset,
            $index,
            $item,
            $x,
            $y,
            $w,
            $h,
            $id,
            $content,
            $fade_in,
            $click
        );

        return true;
    }

    /**
     * Adds a label for non-data text.
     *
     * @param $dataset
     * @param $index
     * @param $x
     * @param $y
     * @param $w
     * @param $h
     * @param $content
     *
     * @return bool
     */
    protected function AddContentLabel($dataset, $index, $x, $y, $w, $h, $content)
    {
        if (!isset($this->data_labels)) {
            $this->data_labels = new DataLabels($this);
        }

        $this->data_labels->AddContentLabel(
            $dataset,
            $index,
            $x,
            $y,
            $w,
            $h,
            $content
        );

        return true;
    }

    /**
     * Draws the data labels.
     */
    protected function DrawDataLabels()
    {
        if (isset($this->settings['label'])) {
            if (!isset($this->data_labels)) {
                $this->data_labels = new DataLabels($this);
            }
            $this->data_labels->Load($this->settings);
        }
        if (isset($this->data_labels)) {
            return $this->data_labels->GetLabels();
        }

        return '';
    }

    /**
     * Returns the position for a data label.
     *
     * @param $dataset
     * @param $index
     * @param $item
     * @param $x
     * @param $y
     * @param $w
     * @param $h
     * @param $label_w
     * @param $label_h
     *
     * @return string
     */
    public function DataLabelPosition(
        $dataset,
        $index,
        &$item,
        $x,
        $y,
        $w,
        $h,
        $label_w,
        $label_h
    ) {
        $pos = $this->ArrayOption($this->data_label_position, $dataset);
        if (empty($pos)) {
            $pos = 'above';
        }

        return $pos;
    }

    public function LoadShapes()
    {
        $this->shapes = new SVGGraphShapeList($this);

        $this->shapes->Load($this->settings);
    }

    public function UnderShapes()
    {
        if (!isset($this->shapes) && isset($this->settings['shape'])) {
            $this->LoadShapes();
        }

        return isset($this->shapes) ? $this->shapes->Draw(SVGG_SHAPE_BELOW) : '';
    }

    public function OverShapes()
    {
        return isset($this->shapes) ? $this->shapes->Draw(SVGG_SHAPE_ABOVE) : '';
    }

    /**
     * Returns TRUE if the position is inside the item.
     *
     * @param $pos
     *
     * @return bool
     */
    public static function IsPositionInside($pos)
    {
        list($hpos, $vpos) = self::TranslatePosition($pos);

        return strpos($hpos . $vpos, 'o') === false;
    }

    /**
     * Sets the styles for data labels.
     *
     * @param $dataset
     * @param $index
     * @param $item
     *
     * @return array
     */
    public function DataLabelStyle($dataset, $index, &$item)
    {
        $style = array(
            'type'           => $this->ArrayOption($this->data_label_type, $dataset),
            'font'           => $this->ArrayOption($this->data_label_font, $dataset),
            'font_size'      => $this->ArrayOption($this->data_label_font_size, $dataset),
            'font_adjust'    => $this->ArrayOption($this->data_label_font_adjust, $dataset),
            'font_weight'    => $this->ArrayOption($this->data_label_font_weight, $dataset),
            'colour'         => $this->ArrayOption($this->data_label_colour, $dataset),
            'altcolour'      => $this->ArrayOption($this->data_label_colour_outside, $dataset),
            'back_colour'    => $this->ArrayOption($this->data_label_back_colour, $dataset),
            'back_altcolour' => $this->ArrayOption($this->data_label_back_colour_outside, $dataset),
            'space'          => $this->ArrayOption($this->data_label_space, $dataset),
            'angle'          => $this->ArrayOption($this->data_label_angle, $dataset),
            'pad_x'          => $this->GetFirst(
                $this->ArrayOption($this->data_label_padding_x, $dataset),
                $this->ArrayOption($this->data_label_padding, $dataset)
            ),
            'pad_y'          => $this->GetFirst(
                $this->ArrayOption($this->data_label_padding_y, $dataset),
                $this->ArrayOption($this->data_label_padding, $dataset)
            ),
            'round'          => $this->ArrayOption($this->data_label_round, $dataset),
            'stroke'         => $this->ArrayOption($this->data_label_outline_colour, $dataset),
            'stroke_width'   => $this->ArrayOption($this->data_label_outline_thickness, $dataset),
            'fill'           => $this->ArrayOption($this->data_label_fill, $dataset),
            'tail_width'     => $this->ArrayOption($this->data_label_tail_width, $dataset),
            'tail_length'    => $this->ArrayOption($this->data_label_tail_length, $dataset),
            'shadow_opacity' => $this->ArrayOption($this->data_label_shadow_opacity, $dataset),
        );

        return $style;
    }

    /**
     * Tail direction is required for some types of label.
     *
     * @param $dataset
     * @param $index
     * @param $hpos
     * @param $vpos
     *
     * @return int
     */
    public function DataLabelTailDirection($dataset, $index, $hpos, $vpos)
    {
        // angle starts at right, goes clockwise
        $angle = 90;
        $pos   = str_replace(array('i', 'o', 'm'), '', $vpos) .
                 str_replace(array('i', 'o', 'c'), '', $hpos);
        switch ($pos) {
            case 'l' :
                $angle = 0;
                break;
            case 'tl' :
                $angle = 45;
                break;
            case 't' :
                $angle = 90;
                break;
            case 'tr' :
                $angle = 135;
                break;
            case 'r' :
                $angle = 180;
                break;
            case 'br' :
                $angle = 225;
                break;
            case 'b' :
                $angle = 270;
                break;
            case 'bl' :
                $angle = 315;
                break;
        }

        return $angle;
    }

    /**
     * Returns the SVG document.
     *
     * @param bool $header
     * @param bool $defer_javascript
     *
     * @return mixed|string
     */
    public function Fetch($header = true, $defer_javascript = true)
    {
        $content = '';
        if ($header) {
            $content .= '<?xml version="1.0"';
            // encoding comes before standalone
            if (strlen($this->encoding) > 0) {
                $content .= " encoding=\"{$this->encoding}\"";
            }
            // '>' is with \n so as not to confuse syntax highlighting
            $content .= ' standalone="no"?' . ">\n";
            if ($this->doctype) {
                $content .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" ' .
                            '"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . "\n";
            }
        }

        // set the precision - PHP default is 14 digits!
        self::$precision = $this->settings['precision'];
        $old_precision   = ini_set('precision', self::$precision);
        // set decimal and thousands for NumString
        self::SetNumStringOptions(
            $this->settings['decimal'],
            $this->settings['thousands']
        );

        // display title and description if available
        $heading = '';
        if ($this->title) {
            $heading .= $this->Element('title', null, null, $this->title);
        }
        if ($this->description) {
            $heading .= $this->Element('desc', null, null, $this->description);
        }

        try {
            $this->CheckValues($this->values);

            if ($this->show_tooltips) {
                $this->LoadJavascript();
            }

            // get the body content from the subclass
            $body = $this->DrawGraph();
        } catch (\Exception $e) {
            $err = $e->getMessage();
            if ($this->exception_details) {
                $err .= ' [' . basename($e->getFile()) . ' #' . $e->getLine() . ']';
            }
            $body = $this->ErrorText($err);
        }

        $svg = array(
            'width'       => $this->width,
            'height'      => $this->height,
            'version'     => '1.1',
            'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
        );
        if ($this->auto_fit) {
            $svg['viewBox'] = "0 0 {$this->width} {$this->height}";
            $svg['width']   = $svg['height'] = '100%';
        }
        if ($this->svg_class) {
            $svg['class'] = $this->svg_class;
        }

        if (!$defer_javascript) {
            $js = $this->FetchJavascript();
            if ($js != '') {
                $heading .= $js;
                $onload = self::$javascript->GetOnload();
                if ($onload != '') {
                    $svg['onload'] = $onload;
                }
            }
        }

        // insert any gradients that are used
        foreach ($this->gradients as $key => $gradient) {
            $this->defs[] = $this->MakeLinearGradient($key);
        }
        // and any patterns
        if (!is_null($this->pattern_list)) {
            $this->pattern_list->MakePatterns($this->defs);
        }

        // show defs and body content
        if (count($this->defs)) {
            $heading .= $this->Element('defs', null, null, implode('', $this->defs));
        }
        if ($this->namespace) {
            $svg['xmlns:svg'] = 'http://www.w3.org/2000/svg';
        } else {
            $svg['xmlns'] = 'http://www.w3.org/2000/svg';
        }

        // add any extra namespaces
        foreach ($this->namespaces as $ns => $url) {
            $svg['xmlns:' . $ns] = $url;
        }

        // display version string
        if ($this->show_version) {
            $text  = array('x' => $this->pad_left, 'y' => $this->height - 3);
            $style = array(
                'font-family' => 'monospace',
                'font-size'   => '12px',
                'font-weight' => 'bold',
            );
            $body .= $this->ContrastText(
                $text['x'],
                $text['y'],
                SVGGRAPH_VERSION,
                'blue',
                'white',
                $style
            );
        }

        $content .= $this->Element('svg', $svg, null, $heading . $body);
        // replace PHP's precision
        ini_set('precision', $old_precision);

        if ($this->minify) {
            $content = preg_replace('/\>\s+\</', '><', $content);
        }

        return $content;
    }

    /**
     * Renders the SVG document.
     *
     * @param bool $header
     * @param bool $content_type
     * @param bool $defer_javascript
     */
    public function Render(
        $header = true,
        $content_type = true,
        $defer_javascript = false
    ) {
        $mime_header = 'Content-type: image/svg+xml; charset=UTF-8';
        try {
            $content = $this->Fetch($header, $defer_javascript);
            if ($content_type) {
                header($mime_header);
            }
            echo $content;
        } catch (\Exception $e) {
            if ($content_type) {
                header($mime_header);
            }
            $this->ErrorText($e);
        }
    }

    /**
     * When using the defer_javascript option, this returns the
     * Javascript block.
     *
     * @param bool $onload_immediate
     * @param bool $cdata_wrap
     * @param bool $no_namespace
     *
     * @return string
     */
    public function FetchJavascript(
        $onload_immediate = true,
        $cdata_wrap = true,
        $no_namespace = true
    ) {
        $js = '';
        if (isset(self::$javascript)) {
            $variables = self::$javascript->GetVariables();
            $functions = self::$javascript->GetFunctions();
            $onload    = self::$javascript->GetOnload();

            if ($variables != '' || $functions != '') {
                if ($onload_immediate) {
                    $functions .= "\n" . "setTimeout(function(){{$onload}},20);";
                }
                $script_attr = array('type' => 'application/ecmascript');
                $script      = "$variables\n$functions\n";
                if (!empty($this->minify_js) && function_exists($this->minify_js)) {
                    $script = call_user_func($this->minify_js, $script);
                }
                if ($cdata_wrap) {
                    $script = "// <![CDATA[\n$script\n// ]]>";
                }
                $namespace = $this->namespace;
                if ($no_namespace) {
                    $this->namespace = false;
                }
                $js = $this->Element('script', $script_attr, null, $script);
                if ($no_namespace) {
                    $this->namespace = $namespace;
                }
            }
        }

        return $js;
    }

    /**
     * Returns a value from the $item, or the member % set.
     *
     * @param      $member
     * @param      $set
     * @param      $item
     * @param null $ikey
     *
     * @return null
     */
    protected function GetFromItemOrMember($member, $set, &$item, $ikey = null)
    {
        $value = is_null($item) ? null : $item->Data(is_null($ikey) ? $member : $ikey);
        if (is_null($value)) {
            $value = is_array($this->{$member})
                ?
                $this->{$member}[$set % count($this->{$member})]
                :
                $this->{$member};
        }

        return $value;
    }

    /**
     * Converts number to string.
     *
     * @param      $n
     * @param null $decimals
     * @param null $precision
     *
     * @return string
     */
    public static function NumString($n, $decimals = null, $precision = null)
    {
        if (is_int($n)) {
            $d = is_null($decimals) ? 0 : $decimals;
        } else {
            if (is_null($precision)) {
                $precision = self::$precision;
            }

            // if there are too many zeroes before other digits, round to 0
            $e = floor(log(abs($n), 10));
            if (-$e > $precision) {
                $n = 0;
            }

            // subtract number of digits before decimal point from precision
            // for precision-based decimals
            $d = is_null($decimals) ? $precision - ($e > 0 ? $e : 0) : $decimals;
        }
        $s = number_format($n, $d, self::$decimal, self::$thousands);

        if (is_null($decimals) && $d && strpos($s, self::$decimal) !== false) {
            list($a, $b) = explode(self::$decimal, $s);
            $b1 = rtrim($b, '0');
            if ($b1 != '') {
                return $a . self::$decimal . $b1;
            }

            return $a;
        }

        return $s;
    }

    /**
     * Sets the number format characters.
     *
     * @param $decimal
     * @param $thousands
     */
    public static function SetNumStringOptions($decimal, $thousands)
    {
        self::$decimal   = $decimal;
        self::$thousands = $thousands;
    }

    /**
     * Returns the minimum value in the array, ignoring NULLs.
     *
     * @param $a
     *
     * @return null
     */
    public static function min(&$a)
    {
        $min = null;
        reset($a);
        while (list(, $v) = each($a)) {
            if (!is_null($v) && (is_null($min) || $v < $min)) {
                $min = $v;
            }
        }

        return $min;
    }
}
