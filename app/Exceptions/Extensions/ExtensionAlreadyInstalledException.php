<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Extensions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Exceptions\ApiErrorResponse;

/**
 * A package would replace an installed extension with the same id, and the caller did not
 * confirm it. Replacing keeps an enabled extension enabled and runs its migrations, so the
 * API answers 409 with both versions for the admin to confirm before trying again.
 */
final class ExtensionAlreadyInstalledException extends InvalidExtensionException
{
    public function __construct(
        public readonly string $identifier,
        public readonly ?string $installedVersion,
        public readonly string $version,
        public readonly bool $enabled,
    ) {
        parent::__construct(sprintf(
            'Extension "%s"%s is already installed; replacing it with v%s must be confirmed.',
            $identifier,
            $installedVersion === null ? '' : ' v'.$installedVersion,
            $version,
        ));
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return null;
        }

        $error = ApiErrorResponse::toArray($this, ['status' => (string) Response::HTTP_CONFLICT])['errors'][0];
        $error['meta'] = array_merge($error['meta'] ?? [], [
            'identifier' => $this->identifier,
            'installed_version' => $this->installedVersion,
            'version' => $this->version,
            'enabled' => $this->enabled,
        ]);

        return new JsonResponse(['errors' => [$error]], Response::HTTP_CONFLICT);
    }
}
