<?php

// j11e\markdown-specific autoloader
spl_autoload_register(function ($class) {
    static $ds = DIRECTORY_SEPARATOR;

    $class = ltrim($class, '\\');
    $class = strtr($class, '\\', $ds);
    $class = explode('\\', $class);

    // this autoloader only works for j11e\markdown\*
    if ($class[0] != "j11e" || $class[1] != "markdown") {
        return false;
    }

    $class = implode($ds, array_slice($class, 2, count($class)-2));
    require "src{$ds}{$class}.php";
});

// composer-generated autoloader
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
}
