<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Support\Facades\File;
use Pterodactyl\Support\JsonValueGuard;

/**
 * The lucide icon names this panel build can render for `nav.icon`. The list is
 * generated with the SDK (npm run sdk:generate) from the bundled lucide release, so
 * a name outside it is valid manifest syntax that renders the default icon here.
 */
final class ExtensionIconCatalog
{
    public const string PATH = 'packages/sdk/icons.json';

    /**
     * The navigation icons a manifest declares that this panel does not ship.
     *
     * @return list<string>
     */
    public function unknownIcons(ExtensionManifest $manifest): array
    {
        $declared = [];
        foreach ($manifest->screens as $screen) {
            if (isset($screen['nav']['icon'])) {
                $declared[] = $screen['nav']['icon'];
            }
        }

        return array_values(array_unique(array_diff($declared, $this->names())));
    }

    /** @return list<string> */
    public function names(): array
    {
        return JsonValueGuard::stringList(File::json(base_path(self::PATH)));
    }
}
