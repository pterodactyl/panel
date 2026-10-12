<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Themes;

use Illuminate\Support\Facades\File;

/**
 * The core view names the SDK publishes in `views.json`. A name under a core namespace
 * (`server.`, `page.`, `layout.`, ...) has to be one of them; any other namespace belongs to
 * a view an extension defines.
 */
class ViewCatalog
{
    public function path(): string
    {
        return base_path('packages/sdk/views.json');
    }

    /**
     * @param  list<string>  $names
     * @return list<string> the names that claim a core namespace but name no core view
     */
    public function unknown(array $names): array
    {
        $known = $this->names();
        if ($known === null) {
            return [];
        }

        $namespaces = array_unique(array_map(fn (string $name): string => explode('.', $name)[0], $known));

        return array_values(array_filter($names, fn (string $name): bool => ! in_array($name, $known, true) && in_array(explode('.', $name)[0], $namespaces, true)));
    }

    /** @return list<string>|null the catalog, a list of names or an object keyed by them; null without one */
    private function names(): ?array
    {
        $data = is_file($this->path()) ? rescue(fn (): mixed => json_decode(File::get($this->path()), true, flags: JSON_THROW_ON_ERROR), report: false) : null;
        if (! is_array($data)) {
            return null;
        }

        return array_values(array_filter(array_is_list($data) ? $data : array_keys($data), is_string(...)));
    }
}
