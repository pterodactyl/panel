<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Collection;
use Pterodactyl\Models\Permission;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;

final readonly class SubuserPermissionCatalog
{
    public function __construct(private ExtensionPermissionRegistry $extensions) {}

    /**
     * Every permission group a subuser can be granted: the core groups followed by
     * those registered by enabled extensions.
     *
     * @return Collection<string, array{description: string, keys: array<string, string>}>
     */
    public function groups(): Collection
    {
        return Permission::permissions()->merge($this->extensions->all());
    }

    /**
     * Every grantable permission key, such as `control.console` or `ext.votes.view`.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        $keys = [];
        foreach ($this->groups() as $prefix => $group) {
            foreach (array_keys($group['keys']) as $key) {
                $keys[] = "{$prefix}.{$key}";
            }
        }

        return $keys;
    }
}
