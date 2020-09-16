<?php

namespace Pterodactyl\Services\Eggs\Sharing;

use Carbon\Carbon;
use Symfony\Component\Yaml\Yaml;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;

class EggExporterService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggExporterService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\EggRepositoryInterface $repository
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // Currently symfony/yaml doesn't support crlf newlines in scalar blocks so we just strip them
    private function crlfToLf($string)
    {
        if (! is_string($string)) {
            return "";
        }

        return str_replace("\r\n", "\n", $string);
    }

    /**
     * Return a YAML representation of an egg and its variables.
     *
     * @param int $egg
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $egg): string
    {
        $egg = $this->repository->getWithExportAttributes($egg);

        $struct = [
            'meta' => [
                'version' => 'PTDL_v2',
                'update_url' => $egg->update_url,
            ],
            'exported_at' => Carbon::now()->toIso8601String(),
            'name' => $egg->name,
            'author' => $egg->author,
            'description' => $this->crlfToLf($egg->description),
            'features' => $egg->features,
            'images' => $egg->docker_images,
            'startup' => $this->crlfToLf($egg->startup),
            'config' => [
                'files' => Yaml::parse($egg->inherit_config_files),
                'startup' => Yaml::parse($egg->inherit_config_startup),
                'logs' => Yaml::parse($egg->inherit_config_logs),
                'stop' => $egg->inherit_config_stop,
            ],
            'scripts' => [
                'installation' => [
                    'script' => $this->crlfToLf($egg->copy_script_install),
                    'container' => $egg->copy_script_container,
                    'entrypoint' => $egg->copy_script_entry,
                ],
            ],
            'variables' => $egg->variables->transform(function ($item) {
                return collect($item->toArray())
                    ->except(['id', 'egg_id', 'created_at', 'updated_at'])
                    ->merge(['description' => $this->crlfToLf($item->description)])
                    ->toArray();
            })->toArray(),
        ];

        return "# DO NOT EDIT: FILE GENERATED AUTOMATICALLY BY PTERODACTYL PANEL - PTERODACTYL.IO\n" .
            Yaml::dump($struct, 8, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }
}
