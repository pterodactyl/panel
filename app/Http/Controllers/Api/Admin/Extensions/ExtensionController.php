<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Extensions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Contracts\Extensions\RemovesExtensions;
use Pterodactyl\Contracts\Extensions\ReplacesExtensionSettingFiles;
use Pterodactyl\Contracts\Extensions\SetsExtensionEnabled;
use Pterodactyl\Contracts\Extensions\UpdatesExtensionSettings;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Extensions\DeleteExtensionRequest;
use Pterodactyl\Http\Requests\Api\Admin\Extensions\GetExtensionsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Extensions\InstallExtensionRequest;
use Pterodactyl\Http\Requests\Api\Admin\Extensions\UpdateExtensionRequest;
use Pterodactyl\Http\Requests\Api\Admin\Extensions\UpdateExtensionSettingsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Extensions\UploadExtensionSettingFileRequest;
use Pterodactyl\Models\Extension;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Extensions', 'Install, inspect, enable, disable, and remove panel extensions.')]
#[ResponseField('description', nullable: true)]
#[ResponseField('author', nullable: true)]
#[ResponseField('provider', nullable: true)]
#[ResponseField('ui_entry', nullable: true)]
#[ResponseField('frontend_entry', nullable: true)]
#[ResponseField('icon', nullable: true)]
#[ResponseField('icon_url', nullable: true)]
class ExtensionController extends AdminApiController
{
    private const array EXTENSION_EXAMPLE = [
        'id' => 'example-extension',
        'name' => 'Example Extension',
        'version' => '1.0.0',
        'description' => 'Example extension',
        'author' => 'Pterodactyl',
        'provider' => 'ExampleExtension\\Provider',
        'icon' => 'puzzle',
        'icon_url' => null,
        'has_ui' => true,
        'ui_entry' => 'dist/client.js',
        'ui_mode' => 'native',
        'frontend_entry' => '/assets/extensions/example-extension/client.js?v=100',
        'installed' => true,
        'enabled' => true,
        'state' => 'enabled',
        'error' => null,
    ];

    private const array LIST_EXAMPLE = [
        'data' => [
            self::EXTENSION_EXAMPLE,
        ],
        'meta' => [
            'enabled' => true,
            'directory' => '/var/www/pterodactyl/extensions',
        ],
    ];

    private const array SETTINGS_EXAMPLE = [
        'data' => [
            'registered' => true,
            'schema' => [
                [
                    'input' => 'curseforge_api_key',
                    'label' => 'CurseForge API Key',
                    'help' => 'Leave blank to keep the stored key.',
                    'field' => 'password',
                    'options' => [],
                    'value' => '********',
                    'constraints' => ['max_length' => null, 'max_items' => null, 'max_kilobytes' => null, 'accept' => []],
                    'visibility' => 'admin',
                ],
            ],
        ],
    ];

    #[Endpoint('List extensions', 'Returns discovered extensions, invalid manifests, and their install state.')]
    #[ScribeResponse(self::LIST_EXAMPLE, description: 'Extensions returned.')]
    public function index(GetExtensionsRequest $request, ExtensionRepository $extensions, ExtensionAssetPublisher $assets): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->extensionList($extensions, $assets),
            'meta' => [
                'enabled' => (bool) config('extensions.enabled'),
                'directory' => $extensions->directory(),
            ],
        ]);
    }

    #[Endpoint('Get extension icon', 'Returns the PNG, JPEG or WebP image an extension declares as its "icon". Extensions with a lucide icon name or no icon return not found.')]
    #[ScribeResponse('', description: 'Icon image returned.')]
    public function icon(GetExtensionsRequest $request, ExtensionRepository $extensions, ExtensionAssetPublisher $assets, string $extension): Response
    {
        $manifest = $extensions->discovered()->get($extension);
        $icon = $manifest instanceof ExtensionManifest ? $assets->icon($manifest) : null;
        abort_if($icon === null, Response::HTTP_NOT_FOUND, "Extension \"{$extension}\" has no image icon.");

        return new Response($icon['contents'], Response::HTTP_OK, [
            'Content-Type' => $icon['mime'],
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    #[Endpoint('Install extension', 'Installs an uploaded .pteroext or .zip extension package.')]
    #[BodyParam('package', 'file', 'Extension archive to install.', required: true)]
    #[BodyParam('enable', 'boolean', 'Enable the extension after installing it.', required: false, example: false)]
    #[BodyParam('replace', 'boolean', 'Replace an installed extension with the same id. Without it, such a package is rejected with a 409 naming the id and both versions.', required: false, example: false)]
    #[ScribeResponse(['data' => self::EXTENSION_EXAMPLE], status: 201, description: 'Extension installed.')]
    public function store(
        InstallExtensionRequest $request,
        ExtensionRepository $extensions,
        InstallsExtensions $installer,
        ExtensionAssetPublisher $assets,
    ): JsonResponse {
        $payload = $request->payload();
        $uploaded = $payload['package'];
        $workdir = storage_path('app'.DIRECTORY_SEPARATOR.'extensions-uploads');
        $filename = Str::random(24).'.'.($uploaded->getClientOriginalExtension() ?: 'zip');

        File::ensureDirectoryExists($workdir);
        $uploaded->move($workdir, $filename);
        $path = $workdir.DIRECTORY_SEPARATOR.$filename;

        try {
            $manifest = $installer->install($path, $payload['enable'], $payload['replace']);
        } finally {
            File::delete($path);
        }

        return new JsonResponse([
            'data' => $this->serializeManifest($manifest, $extensions->records()->get($manifest->id), $assets),
        ], Response::HTTP_CREATED);
    }

    #[Endpoint('Get extension settings', 'Returns the auto-render form schema (with current public values) for an extension that registered a settings definition.')]
    #[ScribeResponse(self::SETTINGS_EXAMPLE, description: 'Settings schema returned.')]
    public function settings(GetExtensionsRequest $request, ExtensionRepository $extensions, ExtensionSettingsRegistry $settingsRegistry, string $extension): JsonResponse
    {
        $definition = $settingsRegistry->get($extension);
        if (! $definition instanceof ExtensionSettingsDefinition) {
            abort_if($extensions->discovered()->get($extension) === null, Response::HTTP_NOT_FOUND, "Extension \"{$extension}\" is not installed.");

            return new JsonResponse(['data' => ['registered' => false, 'schema' => []]]);
        }

        return new JsonResponse(['data' => ['registered' => true, 'schema' => $definition->schema()]]);
    }

    #[Endpoint('Update extension settings', "Validates against each field's type and the extension's declared rules and persists the submitted values. Omitted fields keep their stored values, as do secrets submitted empty or as their mask.")]
    #[BodyParam('settings', 'object', 'Field values keyed by input name, as described by the settings schema.', required: true, example: ['curseforge_api_key' => 'cf-key'])]
    #[ScribeResponse(self::SETTINGS_EXAMPLE, description: 'Settings updated; fresh schema returned.')]
    public function updateSettings(UpdateExtensionSettingsRequest $request, UpdatesExtensionSettings $updateSettings, ExtensionSettingsRegistry $settingsRegistry, string $extension): JsonResponse
    {
        $definition = $settingsRegistry->get($extension);
        abort_if(! $definition instanceof ExtensionSettingsDefinition, Response::HTTP_NOT_FOUND, "Extension \"{$extension}\" has not registered settings (it may be disabled).");

        $schema = $updateSettings->update($definition, $request->settings($definition));

        return new JsonResponse(['data' => ['registered' => true, 'schema' => $schema]]);
    }

    #[Endpoint('Upload extension setting file', "Stores the uploaded file as the value of a file setting and deletes the file it replaces. The type is decided from the file's content and checked against the setting's allow-list and size limit.")]
    #[BodyParam('file', 'file', 'File to store.', required: true)]
    #[ScribeResponse(self::SETTINGS_EXAMPLE, description: 'File stored; fresh schema returned.')]
    public function uploadSettingFile(UploadExtensionSettingFileRequest $request, ReplacesExtensionSettingFiles $files, ExtensionSettingsRegistry $settingsRegistry, string $extension, string $input): JsonResponse
    {
        $definition = $settingsRegistry->get($extension);
        abort_if(! $definition instanceof ExtensionSettingsDefinition, Response::HTTP_NOT_FOUND, "Extension \"{$extension}\" has not registered settings (it may be disabled).");
        abort_unless($definition->fileField($input) instanceof ExtensionSettingDefinition, Response::HTTP_NOT_FOUND, "Setting \"{$input}\" is not a file setting.");

        return new JsonResponse(['data' => ['registered' => true, 'schema' => $files->replace($definition, $input, $request->upload())]]);
    }

    #[Endpoint('Clear extension setting file', 'Deletes the file stored for a file setting, which returns the setting to its default.')]
    #[ScribeResponse(self::SETTINGS_EXAMPLE, description: 'File removed; fresh schema returned.')]
    public function clearSettingFile(UpdateExtensionRequest $request, ReplacesExtensionSettingFiles $files, ExtensionSettingsRegistry $settingsRegistry, string $extension, string $input): JsonResponse
    {
        $definition = $settingsRegistry->get($extension);
        abort_if(! $definition instanceof ExtensionSettingsDefinition, Response::HTTP_NOT_FOUND, "Extension \"{$extension}\" has not registered settings (it may be disabled).");
        abort_unless($definition->fileField($input) instanceof ExtensionSettingDefinition, Response::HTTP_NOT_FOUND, "Setting \"{$input}\" is not a file setting.");

        return new JsonResponse(['data' => ['registered' => true, 'schema' => $files->replace($definition, $input, null)]]);
    }

    #[Endpoint('Enable extension', 'Enables an installed extension, publishes assets, and runs migrations.')]
    #[ScribeResponse(['data' => self::EXTENSION_EXAMPLE], description: 'Extension enabled.')]
    public function enable(
        UpdateExtensionRequest $request,
        SetsExtensionEnabled $installer,
        ExtensionRepository $extensions,
        ExtensionAssetPublisher $assets,
        string $extension,
    ): JsonResponse {
        $installer->setEnabled($extension, true);

        return new JsonResponse(['data' => $this->serializeInstalled($extension, $extensions, $assets)]);
    }

    #[Endpoint('Disable extension', 'Disables an installed extension without removing files or data.')]
    #[ScribeResponse(['data' => self::EXTENSION_EXAMPLE], description: 'Extension disabled.')]
    public function disable(
        UpdateExtensionRequest $request,
        SetsExtensionEnabled $installer,
        ExtensionRepository $extensions,
        ExtensionAssetPublisher $assets,
        string $extension,
    ): JsonResponse {
        $installer->setEnabled($extension, false);

        return new JsonResponse(['data' => $this->serializeInstalled($extension, $extensions, $assets)]);
    }

    #[Endpoint('Remove extension', 'Deletes extension files, published assets, settings, subuser permission grants, and the install record.')]
    #[ScribeResponse(status: 204, description: 'Extension removed.')]
    public function destroy(DeleteExtensionRequest $request, RemovesExtensions $installer, string $extension): Response
    {
        $installer->remove($extension);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extensionList(ExtensionRepository $extensions, ExtensionAssetPublisher $assets): array
    {
        $records = $extensions->records();
        $extensionsList = $extensions->discovered()
            ->map(fn (ExtensionManifest $manifest): array => $this->serializeManifest($manifest, $records->get($manifest->id), $assets))
            ->values();

        foreach ($extensions->discoveryErrors() as $directory => $error) {
            $extensionsList->push([
                'id' => $directory,
                'name' => '(invalid manifest)',
                'version' => null,
                'api' => null,
                'description' => null,
                'author' => null,
                'provider' => null,
                'icon' => null,
                'icon_url' => null,
                'has_ui' => false,
                'ui_entry' => null,
                'ui_mode' => null,
                'frontend_entry' => null,
                'installed' => false,
                'enabled' => false,
                'compatible' => false,
                'state' => 'invalid',
                'error' => $error,
            ]);
        }

        return array_values($extensionsList->sortBy('id')->values()->all());
    }

    /**
     * @return ApiPayload
     */
    private function serializeInstalled(string $identifier, ExtensionRepository $extensions, ExtensionAssetPublisher $assets): array
    {
        $extensions->flushDiscovery();

        $manifest = $extensions->discovered()->get($identifier);
        abort_if($manifest === null, Response::HTTP_NOT_FOUND, "Extension \"{$identifier}\" is not installed.");

        return $this->serializeManifest($manifest, $extensions->records()->get($identifier), $assets);
    }

    /**
     * @return ApiPayload
     */
    private function serializeManifest(ExtensionManifest $manifest, ?Extension $record, ExtensionAssetPublisher $assets): array
    {
        return [
            'id' => $manifest->id,
            'name' => $manifest->name,
            'version' => $manifest->version,
            'description' => $manifest->description,
            'author' => $manifest->author,
            'provider' => $manifest->provider,
            'icon' => $manifest->iconName(),
            'icon_url' => $assets->iconUrl($manifest),
            'has_ui' => $manifest->hasUi(),
            'ui_entry' => $manifest->uiEntry,
            'ui_mode' => $manifest->uiMode,
            'frontend_entry' => $manifest->hasUi() ? $assets->entryUrl($manifest) : null,
            'installed' => $record instanceof Extension,
            'enabled' => (bool) ($record->enabled ?? false),
            'state' => $this->state($record),
            'error' => $record?->error,
        ];
    }

    private function state(?Extension $record): string
    {
        return match (true) {
            ! $record instanceof Extension => 'not_registered',
            $record->error !== null => 'error',
            $record->enabled => 'enabled',
            default => 'disabled',
        };
    }
}
