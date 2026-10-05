<?php

declare(strict_types=1);

return [
    Pterodactyl\Providers\ActivityLogServiceProvider::class,
    Pterodactyl\Providers\ActionServiceProvider::class,
    Pterodactyl\Providers\AppServiceProvider::class,
    Pterodactyl\Providers\AuthServiceProvider::class,
    Pterodactyl\Providers\BackupsServiceProvider::class,
    Pterodactyl\Providers\BladeServiceProvider::class,
    Pterodactyl\Providers\EventServiceProvider::class,
    Pterodactyl\Providers\HashidsServiceProvider::class,
    Pterodactyl\Providers\ViewComposerServiceProvider::class,
    // Last so extensions register against a fully-bound core container.
    Pterodactyl\Providers\ExtensionServiceProvider::class,
];
