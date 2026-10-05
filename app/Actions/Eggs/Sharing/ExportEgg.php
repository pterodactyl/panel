<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Eggs\Sharing;

use Illuminate\Support\Collection;
use JsonException;
use Pterodactyl\Contracts\Eggs\ExportsEggs;
use Pterodactyl\Models\Egg;

final class ExportEgg implements ExportsEggs
{
    /**
     * Return a JSON representation of an egg and its variables.
     *
     * @throws JsonException
     */
    public function export(Egg $egg): string
    {
        $egg->loadMissing('scriptFrom', 'configFrom', 'variables');

        $variables = [];
        foreach ($egg->variables as $item) {
            $variables[] = [
                'name' => $item->name,
                'description' => $item->description,
                'env_variable' => $item->env_variable,
                'default_value' => $item->default_value,
                'user_viewable' => $item->user_viewable,
                'user_editable' => $item->user_editable,
                'rules' => $item->rules,
                'field_type' => 'text',
            ];
        }

        $struct = [
            '_comment' => 'DO NOT EDIT: FILE GENERATED AUTOMATICALLY BY PTERODACTYL PANEL - PTERODACTYL.IO',
            'meta' => [
                'version' => Egg::EXPORT_VERSION,
                'update_url' => $egg->update_url,
            ],
            'exported_at' => now()->toAtomString(),
            'name' => $egg->name,
            'author' => $egg->author,
            'description' => $egg->description,
            'features' => $egg->features,
            'docker_images' => $egg->docker_images,
            'file_denylist' => Collection::make($egg->inherit_file_denylist)->filter(fn (string $value): bool => $value !== ''),
            'startup' => $egg->startup,
            'config' => [
                'files' => $egg->inherit_config_files,
                'startup' => $egg->inherit_config_startup,
                'logs' => $egg->inherit_config_logs,
                'stop' => $egg->inherit_config_stop,
            ],
            'scripts' => [
                'installation' => [
                    'script' => $egg->copy_script_install,
                    'container' => $egg->copy_script_container,
                    'entrypoint' => $egg->copy_script_entry,
                ],
            ],
            'variables' => $variables,
        ];

        return json_encode($struct, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
