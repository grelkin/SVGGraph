<?php

use GGS\SVGGraph\SVGGraph;

class SVGGraphTest extends PHPUnit_Framework_TestCase
{
    public function testSample1()
    {
        $width    = 500;
        $height   = 400;
        $settings = array(
            'back_colour' => 'white',
            'graph_title' => 'Start of Fibonacci series'
        );

        $graph = new SVGGraph($width, $height, $settings);

        $colours = array(
            'red',
            'green',
            '#00ffff',
            'rgb(100,200,100)',
            array('red', 'green'),
            array('blue', 'pattern' => 'spot'),
        );
        $graph->Colours($colours);
        $graph->Values(0, 1, 1, 2, 3, 5, 8, 13, 21);
        $this->assertEquals(file_get_contents(__DIR__ . '/fixtures/sample1.svg'), $graph->Fetch('BarGraph'));
    }

    public function testSample2()
    {
        $graph          = new SVGGraph(640, 480);
        $graph->colours = array('red', 'green', 'blue');
        $graph->Values(100, 200, 150);
        $graph->Links('/Tom/', '/Dick/', '/Harry/');
        $this->assertEquals(file_get_contents(__DIR__ . '/fixtures/sample2.svg'), $graph->Fetch('BarGraph'));
    }

    public function testSample3()
    {
        $width    = 500;
        $height   = 400;
        $settings = array(
            'back_colour' => 'white',
            'graph_title' => 'Start of Fibonacci series'
        );

        $settings['structured_data'] = true;
        $settings['structure']       = array(
            'key'   => 'Name',
            'value' => 'Height'
        );

        $graph = new SVGGraph($width, $height, $settings);

        $colours = array(
            array('blue', 'pattern' => 'spot'),
        );
        $graph->Colours($colours);

        $values = array(
            array('Id' => 1, 'Name' => 'Bob', 'Age' => 51, 'Height' => 170),
            array('Id' => 2, 'Name' => 'Alice', 'Age' => 45, 'Height' => 175),
            array('Id' => 3, 'Name' => 'Frank', 'Age' => 32, 'Height' => 182),
            array('Id' => 4, 'Name' => 'Susan', 'Age' => 35, 'Height' => 185)
        );

        $graph->Values($values);
        $this->assertEquals(file_get_contents(__DIR__ . '/fixtures/sample3.svg'), $graph->Fetch('GroupedBarGraph'));
    }

    public function testSample4()
    {
        $width    = 500;
        $height   = 400;
        $settings = array(
            'back_colour' => 'white',
            'graph_title' => 'Start of Fibonacci series'
        );
        $values   = array(
            array(
                "January",
                10,
                30,
                "svggraph-using.php",
                "svggraph-embed.php",
                "Ten\nItems",
                "Bob",
                "**",
                "#f00",
                "#f0f"
            ),
            array(
                "February",
                6,
                20,
                "svggraph.php",
                "svggraph-settings.php",
                "Six\nItems",
                "Anne",
                "*",
                "#f63",
                "#63f"
            ),
            array(
                "March",
                13,
                18,
                "svggraph-bar.php",
                "svggraph-bar3d.php",
                "Thirteen\nItems",
                "Sue",
                "***",
                "#f93",
                "#93f"
            ),
            array(
                "April",
                16,
                22,
                "svggraph-horizontal.php",
                "svggraph-line.php",
                "Sixteen\nItems",
                "Frank",
                "***",
                "#fc0",
                "#c0f"
            ),
            array(
                "May",
                18,
                25,
                "svggraph-radar.php",
                "svggraph-scatter.php",
                "Eighteen\nItems",
                "Alan",
                "****",
                "#9c0",
                "#63c"
            ),
            array(
                "June",
                16,
                28,
                "svggraph-pie.php",
                "svggraph-misc.php",
                "Sixteen\nItems",
                "Vera",
                "***",
                "#3f0",
                "#00f"
            )
        );

        $settings['structure'] = array(
            'key'     => 0,
            'value'   => array(1, 2),
            'link'    => array(3, 4),
            'tooltip' => array(5, 6),
            'label'   => array(7, 6),
            'colour'  => array(8, 9)
        );

        $graph = new SVGGraph($width, $height, $settings);

        $graph->Values($values);
        $this->assertEquals(file_get_contents(__DIR__ . '/fixtures/sample4.svg'), $graph->Fetch('GroupedBarGraph'));
    }

    public function testSample5()
    {
        $settings = array(
            'back_colour'          => '#eee',
            'stroke_colour'        => '#000',
            'back_stroke_width'    => 0,
            'back_stroke_colour'   => '#eee',
            'axis_colour'          => '#333',
            'axis_overlap'         => 2,
            'axis_font'            => 'Georgia',
            'axis_font_size'       => 10,
            'grid_colour'          => '#666',
            'label_colour'         => '#000',
            'pad_right'            => 20,
            'pad_left'             => 20,
            'link_base'            => '/',
            'link_target'          => '_top',
            'minimum_grid_spacing' => 20
        );

        $values = array(
            array('Dough' => 30, 'Ray' => 50, 'Me' => 40, 'So' => 25, 'Far' => 45, 'Lard' => 35),
            array(
                'Dough' => 20,
                'Ray'   => 30,
                'Me'    => 20,
                'So'    => 15,
                'Far'   => 25,
                'Lard'  => 35,
                'Tea'   => 45
            )
        );

        $colours = array(array('red', 'yellow'), array('blue', 'white'));
        $links   = array(
            'Dough' => 'jpegsaver.php',
            'Ray'   => 'crcdropper.php',
            'Me'    => 'svggraph.php'
        );

        $graph          = new SVGGraph(300, 200, $settings);
        $graph->colours = $colours;

        $graph->Values($values);
        $graph->Links($links);
        $this->assertEquals(file_get_contents(__DIR__ . '/fixtures/sample5.svg'), $graph->Fetch('BarGraph'));
    }
}
