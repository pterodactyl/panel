<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use InvalidArgumentException;

/**
 * The subuser permission groups extensions have registered this boot, keyed by
 * `ext.<extension id>`. Populated by ExtensionProvider::registerPermissions() while
 * providers boot; consumed through SubuserPermissionCatalog.
 */
class ExtensionPermissionRegistry
{
    public const string KEY_REGEX = '/^[a-z][a-z0-9-]{0,47}$/D';

    /** OpenAPI pattern every extension permission matches: `ext.<extension id>.<key>`. Wrap it in `/.../D` for PHP. */
    public const string PERMISSION_PATTERN = '^ext\.[a-z][a-z0-9-]{0,47}\.[a-z][a-z0-9-]{0,47}$';

    /** @var array<string, array{description: string, keys: array<string, string>}> */
    private array $groups = [];

    /** The permission group an extension's keys live under, e.g. `ext.votes`. */
    public static function group(string $identifier): string
    {
        return 'ext.'.$identifier;
    }

    /**
     * @param  array<string, string>  $keys  permission key => description
     */
    public function register(string $identifier, string $description, array $keys): void
    {
        throw_if($keys === [], InvalidArgumentException::class, sprintf('Extension "%s" must register at least one permission.', $identifier));

        foreach (array_keys($keys) as $key) {
            throw_unless(preg_match(self::KEY_REGEX, $key) === 1, InvalidArgumentException::class, sprintf('Extension permission "%s" must match %s.', $key, self::KEY_REGEX));
        }

        $this->groups[self::group($identifier)] = ['description' => $description, 'keys' => $keys];
    }

    /** @return array<string, array{description: string, keys: array<string, string>}> */
    public function all(): array
    {
        return $this->groups;
    }

    public function unregister(string $identifier): void
    {
        unset($this->groups[self::group($identifier)]);
    }
}
