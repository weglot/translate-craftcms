<?php

$base = __DIR__.'/../dependencies_scoped';

$candidates = [
    $base.'/simplehtmldom/src/simple_html_dom.php',
    $base.'/simple_html_dom.php',
];

foreach ($candidates as $file) {
    if (is_file($file)) {
        require_once $file;
        break;
    }
}
