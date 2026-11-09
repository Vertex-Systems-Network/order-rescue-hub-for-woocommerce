<?php

// Composer runtime bootstrap for this plugin's PSR-4 map.
// Composer's ClassLoader has a global class name and can already be loaded by another plugin.
// Reuse that class across plugin copies instead of declaring it a second time.
if (!class_exists('\\Composer\\Autoload\\ClassLoader', false)) {
    require_once __DIR__ . '/ClassLoader.php';
}

$loader = new \Composer\Autoload\ClassLoader(dirname(__DIR__, 2));
$prefixes = require __DIR__ . '/autoload_psr4.php';

foreach ($prefixes as $prefix => $directories) {
    $loader->setPsr4($prefix, $directories);
}

$loader->register(true);

return $loader;
