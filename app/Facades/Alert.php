<?php

declare(strict_types=1);

namespace Pterodactyl\Facades;

use Illuminate\Support\Facades\Facade;
use Pterodactyl\Support\Alerts\AlertsMessageBag;

/**
 * @method static AlertsMessageBag success(string $message)
 * @method static AlertsMessageBag danger(string $message)
 * @method static AlertsMessageBag warning(string $message)
 * @method static AlertsMessageBag info(string $message)
 * @method static AlertsMessageBag flash()
 * @method static array<string, list<string>> getMessages()
 */
class Alert extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AlertsMessageBag::class;
    }
}
