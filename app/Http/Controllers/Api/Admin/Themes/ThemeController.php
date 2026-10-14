<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Themes;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Extensions\ReplacesExtensionSettingFiles;
use Pterodactyl\Contracts\Extensions\UpdatesExtensionSettings;
use Pterodactyl\Contracts\Themes\ActivatesThemes;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Themes\GetThemesRequest;
use Pterodactyl\Http\Requests\Api\Admin\Themes\UpdateThemeRequest;
use Pterodactyl\Http\Requests\Api\Admin\Themes\UpdateThemeSettingsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Themes\UploadThemeImageRequest;
use Pterodactyl\Http\Resources\Api\Admin\ThemeResource;
use Pterodactyl\Http\Resources\Api\Admin\ThemeSettingResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Themes\ThemeRepository;
use Pterodactyl\Services\Themes\ThemeResolver;
use Pterodactyl\Services\Themes\ThemeSetting;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Themes', 'Activate, preview and configure the theme the panel renders with.')]
#[ResponseField('attributes.version', nullable: true)]
#[ResponseField('attributes.description', nullable: true)]
#[ResponseField('attributes.author', nullable: true)]
#[ResponseField('attributes.icon', nullable: true)]
#[ResponseField('attributes.icon_url', nullable: true)]
#[ResponseField('attributes.extends', 'string', 'The theme this one is built on; null for the built-in classic theme.', nullable: true)]
#[ResponseField('attributes.problem', 'string', 'Why the theme cannot be activated right now.', nullable: true)]
#[ResponseField('meta.preview', 'string', 'The theme the signed-in administrator is previewing in this session.', nullable: true)]
class ThemeController extends AdminApiController
{
    private const array THEME_EXAMPLE = [
        'object' => 'theme',
        'attributes' => [
            'id' => 'aurora',
            'name' => 'Aurora',
            'version' => '1.0.0',
            'description' => 'A softer panel with a top navigation bar.',
            'author' => 'Pterodactyl',
            'icon' => 'palette',
            'icon_url' => null,
            'builtin' => false,
            'enabled' => true,
            'active' => true,
            'previewing' => false,
            'extends' => 'classic',
            'settings_count' => 2,
            'problem' => null,
        ],
    ];

    private const array SETTING_LIST_EXAMPLE = [
        'object' => 'list',
        'data' => [[
            'object' => 'theme_setting',
            'attributes' => [
                'key' => 'accent',
                'type' => 'color',
                'label' => 'Accent',
                'help' => null,
                'tab' => null,
                'default' => '#8b5cf6',
                'value' => '#8b5cf6',
                'options' => [],
                'min' => null,
                'max' => null,
                'step' => null,
                'unit' => null,
                'max_length' => null,
                'accept' => [],
                'max_kilobytes' => null,
            ],
        ]],
    ];

    #[Endpoint('List themes', 'Returns the built-in classic theme and every installed theme, with the active one and whether each can be activated.')]
    #[ScribeResponse(['object' => 'list', 'data' => [self::THEME_EXAMPLE], 'meta' => ['active' => 'aurora', 'preview' => null, 'safe_mode' => false]], description: 'Themes returned.')]
    public function index(GetThemesRequest $request, ThemeRepository $themes, ExtensionRepository $extensions, ExtensionAssetPublisher $assets): ApiResourceCollection
    {
        $items = [$this->item($request, $themes, $extensions, $assets, null)];
        foreach ($themes->installed() as $theme) {
            $items[] = $this->item($request, $themes, $extensions, $assets, $theme);
        }

        return ThemeResource::collection($items)->withMeta([
            'active' => $themes->active(),
            'preview' => $this->previewing($request),
            'safe_mode' => $request->hasSession() && $request->session()->get(ThemeResolver::SAFE_MODE) === true,
        ]);
    }

    #[Endpoint('Activate theme', 'Makes an installed, enabled theme the one every visitor sees. Activating "classic" resets the panel.')]
    #[ScribeResponse(self::THEME_EXAMPLE, description: 'Theme activated.')]
    public function activate(UpdateThemeRequest $request, ActivatesThemes $activates, ThemeRepository $themes, ExtensionRepository $extensions, ExtensionAssetPublisher $assets, string $theme): ThemeResource
    {
        $manifest = $this->theme($themes, $theme);
        $activates->activate($theme);

        Activity::event('admin:theme.activate')->property('theme', $theme)->log();

        return ThemeResource::make($this->item($request, $themes, $extensions, $assets, $manifest));
    }

    #[Endpoint('Preview theme', 'Shows a theme to the signed-in administrator only, for the rest of this session, without activating it.')]
    #[ScribeResponse(self::THEME_EXAMPLE, description: 'Preview started.')]
    public function preview(GetThemesRequest $request, ThemeRepository $themes, ExtensionRepository $extensions, ExtensionAssetPublisher $assets, string $theme): ThemeResource
    {
        $manifest = $this->theme($themes, $theme);
        $problem = $themes->activationProblem($theme);
        throw_if($problem !== null, InvalidExtensionException::class, $problem);

        $request->session()->put(ThemeResolver::PREVIEW, $theme);

        return ThemeResource::make($this->item($request, $themes, $extensions, $assets, $manifest));
    }

    #[Endpoint('Stop theme preview', 'Shows the active theme to the signed-in administrator again.')]
    #[ScribeResponse(status: 204, description: 'Preview stopped.')]
    public function stopPreview(GetThemesRequest $request): Response
    {
        $request->session()->forget(ThemeResolver::PREVIEW);

        return $this->returnNoContent();
    }

    #[Endpoint('Get theme settings', 'Returns the settings a theme declares, with their current values. Classic has none.')]
    #[ScribeResponse(self::SETTING_LIST_EXAMPLE, description: 'Settings returned.')]
    public function settings(GetThemesRequest $request, ThemeRepository $themes, string $theme): ApiResourceCollection
    {
        return $this->settingList($themes, $this->theme($themes, $theme));
    }

    #[Endpoint('Update theme settings', "Validates each submitted value against its setting's type and stores it. Omitted settings keep their values and null restores a setting's default.")]
    #[BodyParam('settings', 'object', 'Values keyed by setting name, as listed by the settings endpoint.', required: true, example: ['accent' => '#8b5cf6', 'compact' => true])]
    #[ScribeResponse(self::SETTING_LIST_EXAMPLE, description: 'Settings updated; fresh settings returned.')]
    public function updateSettings(UpdateThemeSettingsRequest $request, UpdatesExtensionSettings $updates, ThemeRepository $themes, string $theme): ApiResourceCollection
    {
        $manifest = $this->theme($themes, $theme);
        abort_if(! $manifest instanceof ExtensionManifest, Response::HTTP_NOT_FOUND, 'The classic theme has no settings.');
        $definition = $themes->settings($manifest);
        $settings = $request->settings($definition);
        $updates->update($definition, $settings);

        Activity::event('admin:theme.settings')->property(['theme' => $theme, 'settings' => array_keys($settings)])->log();

        return $this->settingList($themes, $manifest);
    }

    #[Endpoint('Upload theme image', "Stores the uploaded image as the value of an image setting and deletes the image it replaces. The type is decided from the file's content.")]
    #[BodyParam('file', 'file', 'Image to store.', required: true)]
    #[ScribeResponse(self::SETTING_LIST_EXAMPLE, description: 'Image stored; fresh settings returned.')]
    public function uploadImage(UploadThemeImageRequest $request, ReplacesExtensionSettingFiles $files, ThemeRepository $themes, string $theme, string $setting): ApiResourceCollection
    {
        return $this->replaceImage($files, $themes, $theme, $setting, $request->upload());
    }

    #[Endpoint('Clear theme image', 'Deletes the image stored for an image setting, which returns the setting to no image.')]
    #[ScribeResponse(self::SETTING_LIST_EXAMPLE, description: 'Image removed; fresh settings returned.')]
    public function clearImage(UpdateThemeRequest $request, ReplacesExtensionSettingFiles $files, ThemeRepository $themes, string $theme, string $setting): ApiResourceCollection
    {
        return $this->replaceImage($files, $themes, $theme, $setting, null);
    }

    private function replaceImage(ReplacesExtensionSettingFiles $files, ThemeRepository $themes, string $theme, string $setting, ?UploadedFile $upload): ApiResourceCollection
    {
        $manifest = $this->theme($themes, $theme);
        abort_unless($manifest instanceof ExtensionManifest && ($manifest->theme->settings[$setting]->type ?? null) === 'image', Response::HTTP_NOT_FOUND, "Setting \"{$setting}\" is not an image setting.");

        $files->replace($themes->settings($manifest), $setting, $upload);
        Activity::event($upload instanceof UploadedFile ? 'admin:theme.image' : 'admin:theme.image-clear')->property(['theme' => $theme, 'setting' => $setting])->log();

        return $this->settingList($themes, $manifest);
    }

    /** The installed theme, null for classic; any other id is not found. */
    private function theme(ThemeRepository $themes, string $id): ?ExtensionManifest
    {
        if ($id === ThemeRepository::CLASSIC) {
            return null;
        }

        $theme = $themes->installed()->get($id);
        abort_if($theme === null, Response::HTTP_NOT_FOUND, "Theme \"{$id}\" is not installed.");

        return $theme;
    }

    private function settingList(ThemeRepository $themes, ?ExtensionManifest $theme): ApiResourceCollection
    {
        $stored = $theme instanceof ExtensionManifest ? $themes->settings($theme) : null;
        $items = array_map(fn (ThemeSetting $setting): array => [
            'setting' => $setting,
            'value' => $setting->value($stored?->get(ThemeSetting::KEY_PREFIX.$setting->key)),
        ], array_values($theme?->theme->settings ?? []));

        return ThemeSettingResource::collection($items);
    }

    /**
     * @return array{theme: ExtensionManifest|null, icon_url: string|null, enabled: bool, active: bool, previewing: bool, problem: string|null}
     */
    private function item(Request $request, ThemeRepository $themes, ExtensionRepository $extensions, ExtensionAssetPublisher $assets, ?ExtensionManifest $theme): array
    {
        $id = $theme->id ?? ThemeRepository::CLASSIC;

        return [
            'theme' => $theme,
            'icon_url' => $theme instanceof ExtensionManifest ? $assets->iconUrl($theme) : null,
            'enabled' => ! $theme instanceof ExtensionManifest || once(fn (): Collection => $extensions->records())->get($id)?->enabled === true,
            'active' => $themes->active() === $id,
            'previewing' => $this->previewing($request) === $id,
            'problem' => $themes->activationProblem($id),
        ];
    }

    private function previewing(Request $request): ?string
    {
        $preview = $request->hasSession() ? $request->session()->get(ThemeResolver::PREVIEW) : null;

        return is_string($preview) ? $preview : null;
    }
}
