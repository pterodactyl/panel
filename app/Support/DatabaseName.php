<?php

declare(strict_types=1);

namespace Pterodactyl\Support;

final class DatabaseName
{
    public static function generateUnique(string $name, int $serverId): string
    {
        return sprintf('s%d_%s', $serverId, mb_substr($name, 0, 48 - mb_strlen("s{$serverId}_")));
    }
}
