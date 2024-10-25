<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;

interface Plugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string;

    public function getProperties(): PluginPropertyItem;

    public static function isAvailable(): bool;
}
