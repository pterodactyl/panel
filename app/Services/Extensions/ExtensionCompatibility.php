<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Composer\Semver\Semver;
use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Support\JsonValueGuard;

final class ExtensionCompatibility
{
    /**
     * @param  Collection<string, ExtensionManifest>  $enabled
     * @return array{manifests: Collection<string, ExtensionManifest>, errors: array<string, string>}
     */
    public function resolve(Collection $enabled): array
    {
        $remaining = $enabled->all();
        $accepted = [];
        $errors = [];
        foreach ($remaining as $id => $manifest) {
            $reason = $this->failureReason($manifest, $enabled);
            if ($reason !== null) {
                $errors[$id] = $reason;
                unset($remaining[$id]);
            }
        }

        while ($remaining !== []) {
            $progress = false;
            foreach ($remaining as $id => $manifest) {
                $dependencies = array_keys($manifest->requiredExtensions);
                $failed = array_intersect($dependencies, array_keys($errors));
                if ($failed !== []) {
                    $errors[$id] = sprintf('Extension "%s" requires unavailable extension "%s".', $id, reset($failed));
                } elseif (array_diff($dependencies, array_keys($accepted)) === []) {
                    $accepted[$id] = $manifest;
                } else {
                    continue;
                }

                unset($remaining[$id]);
                $progress = true;
            }

            if (! $progress) {
                foreach (array_keys($remaining) as $id) {
                    $errors[$id] = sprintf('Extension "%s" has a circular extension dependency.', $id);
                }

                break;
            }
        }

        return ['manifests' => collect($accepted), 'errors' => $errors];
    }

    /** @param Collection<string, ExtensionManifest> $enabled */
    public function assertCompatible(ExtensionManifest $manifest, Collection $enabled): void
    {
        $errors = $this->resolve((clone $enabled)->put($manifest->id, $manifest))['errors'];
        // A claim both sides make is reported from the side of the extension being checked.
        $reason = $errors[$manifest->id] ?? reset($errors);
        throw_if($reason !== false, InvalidExtensionException::class, $reason);
    }

    /** @param Collection<string, ExtensionManifest> $enabled */
    public function assertCanDisable(string $identifier, Collection $enabled): void
    {
        foreach ($enabled as $manifest) {
            throw_if(isset($manifest->requiredExtensions[$identifier]), InvalidExtensionException::class, "Disable dependent extension \"{$manifest->id}\" before \"{$identifier}\".");
        }
    }

    public function assertRuntimeCompatible(ExtensionManifest $manifest): void
    {
        $reason = $this->runtimeFailureReason($manifest);
        throw_if($reason !== null, InvalidExtensionException::class, $reason);
    }

    /** @param Collection<string, ExtensionManifest> $enabled */
    private function failureReason(ExtensionManifest $manifest, Collection $enabled): ?string
    {
        $reason = $this->runtimeFailureReason($manifest);
        if ($reason !== null) {
            return $reason;
        }

        foreach ($enabled as $other) {
            if ($other->id === $manifest->id) {
                continue;
            }

            $conflicts = array_intersect($manifest->components, $other->components);
            if ($conflicts !== []) {
                return sprintf('Extension "%s" cannot replace "%s": enabled extension "%s" also declares it.', $manifest->id, reset($conflicts), $other->id);
            }

            $prefixes = array_intersect($manifest->rootPrefixes, $other->rootPrefixes);
            if ($prefixes !== []) {
                return sprintf('Extension "%s" cannot claim root path "/%s": enabled extension "%s" also declares it.', $manifest->id, reset($prefixes), $other->id);
            }

            if ($manifest->uiPrefix !== null && $manifest->uiPrefix === $other->uiPrefix) {
                return sprintf('Extension "%s" cannot use Tailwind prefix "%s": enabled extension "%s" also declares it.', $manifest->id, $manifest->uiPrefix, $other->id);
            }

            // Overlapping PSR-4 prefixes would let one extension's classes, its provider
            // included, be loaded from the other's package.
            foreach (array_keys($manifest->autoload) as $namespace) {
                $claimed = array_find(array_keys($other->autoload), fn (string $prefix): bool => ExtensionManifest::namespacesOverlap($namespace, $prefix));
                if ($claimed !== null) {
                    return sprintf('Extension "%s" cannot autoload "%s": enabled extension "%s" autoloads "%s".', $manifest->id, $namespace, $other->id, $claimed);
                }
            }
        }

        foreach ($manifest->requiredExtensions as $id => $constraint) {
            $dependency = $enabled->get($id);
            if ($dependency === null || ! Semver::satisfies($dependency->version, $constraint)) {
                return sprintf('Extension "%s" requires enabled extension "%s" %s.', $manifest->id, $id, $constraint);
            }
        }

        return null;
    }

    private function runtimeFailureReason(ExtensionManifest $manifest): ?string
    {
        $versions = [
            'panel' => JsonValueGuard::string(config('extensions.panel_version')),
            'sdk' => JsonValueGuard::string(config('extensions.sdk_version')),
            'php' => PHP_VERSION,
        ];
        foreach ($manifest->requirements as $runtime => $constraint) {
            if (! Semver::satisfies($versions[$runtime], $constraint)) {
                return sprintf('Extension "%s" requires %s %s; installed version is %s.', $manifest->id, $runtime, $constraint, $versions[$runtime]);
            }
        }

        return null;
    }
}
