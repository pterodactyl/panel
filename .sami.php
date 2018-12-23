<?php

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir = __DIR__ . '/app');

return new Sami($iterator, array(
    'title'                => 'Pterodactyl',
    'build_dir'            => __DIR__ . '/.sami/build',
    'cache_dir'            => __DIR__ . '/.sami/cache',
    'default_opened_level' => 2,
));
