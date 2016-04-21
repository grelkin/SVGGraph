<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../tests/phpunit/SVGGraphTest.php';

$test = new SVGGraphTest();

$time = microtime(true);

$test->testJMeterGraph();

$time = microtime(true) - $time;

print "$time sec\n";
print memory_get_peak_usage(true) . " bytes\n";
