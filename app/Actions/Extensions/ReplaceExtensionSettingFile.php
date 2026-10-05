<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Extensions;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Extensions\ReplacesExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettingDefinition;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\Extensions\ExtensionSettingsDefinition;
use Throwable;

final readonly class ReplaceExtensionSettingFile implements ReplacesExtensionSettingFiles
{
    public function __construct(private ExtensionSettingFiles $files) {}

    /**
     * @return list<ExtensionSettingField>
     */
    public function replace(ExtensionSettingsDefinition $definition, string $input, ?UploadedFile $upload): array
    {
        $field = $definition->fileField($input);
        throw_unless($field instanceof ExtensionSettingDefinition, ValidationException::withMessages(['file' => sprintf('Setting "%s" does not accept a file.', $input)]));

        $extension = $definition->extension();
        $constraints = $field->fileConstraints();
        $name = $upload instanceof UploadedFile ? $this->files->store($extension, $upload, $constraints['mimes'], $constraints['max_kilobytes']) : null;

        try {
            $previous = $definition->replaceFile($input, $name);
        } catch (Throwable $throwable) {
            if ($name !== null) {
                rescue(fn () => $this->files->delete($extension, $name));
            }

            throw $throwable;
        }

        if ($previous !== null) {
            rescue(fn () => $this->files->delete($extension, $previous));
        }

        return $definition->schema();
    }
}
