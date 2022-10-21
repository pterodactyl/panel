<?php

namespace Pterodactyl\Extensions\Themes;

class Theme
{
    public function js($path): string
    {
        return sprintf('<script src="%s"></script>' . PHP_EOL, $this->getUrl($path));
    }

    public function css($path): string
    {
        return sprintf('<link media="all" type="text/css" rel="stylesheet" href="%s"/>' . PHP_EOL, $this->getUrl($path));
    }

    protected function getUrl($path): string
    {
        return '/themes/pterodactyl/' . ltrim($path, '/');
    }
}
