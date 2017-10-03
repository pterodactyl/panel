<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Exporter;

use Carbon\Carbon;
use Sabre\Xml\Writer;
use Sabre\Xml\Service;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;

class XMLExporterService
{
    const XML_OPTION_NAMESPACE = '{https://pterodactyl.io/exporter/option/}';

    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Sabre\Xml\Service
     */
    protected $xml;

    /**
     * XMLExporterService constructor.
     *
     * @param \Carbon\Carbon                                                     $carbon
     * @param \Sabre\Xml\Service                                                 $xml
     * @param \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface $repository
     */
    public function __construct(
        Carbon $carbon,
        Service $xml,
        ServiceOptionRepositoryInterface $repository
    ) {
        $this->carbon = $carbon;
        $this->repository = $repository;
        $this->xml = $xml;

        $this->xml->namespaceMap = [
            str_replace(['{', '}'], '', self::XML_OPTION_NAMESPACE) => 'p',
        ];
    }

    /**
     * Return an XML structure to represent this service option.
     *
     * @param int|\Pterodactyl\Models\ServiceOption $option
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($option): string
    {
        if (! $option instanceof ServiceOption) {
            $option = $this->repository->find($option);
        }

        $struct = [
            'exported_at' => $this->carbon->now()->toIso8601String(),
            'name' => $option->name,
            'author' => array_get(explode(':', $option->tag), 0),
            'tag' => $option->tag,
            'description' => $option->description,
            'image' => $option->docker_image,
            'config' => [
                'files' => $option->config_files,
                'startup' => $option->config_startup,
                'logs' => $option->config_logs,
                'stop' => $option->config_stop,
            ],
            'scripts' => [
                'installation' => [
                    'script' => function (Writer $writer) use ($option) {
                        return $writer->writeCData($option->copy_script_install);
                    },
                ],
            ],
        ];

        return $this->xml->write(self::XML_OPTION_NAMESPACE . 'root', $this->recursiveArrayKeyPrepend($struct));
    }

    /**
     * @param array  $array
     * @param string $prepend
     *
     * @return array
     */
    protected function recursiveArrayKeyPrepend(array $array, $prepend = self::XML_OPTION_NAMESPACE): array
    {
        $parsed = [];
        foreach ($array as $k => &$v) {
            $k = $prepend . $k;

            if (is_array($v)) {
                $v = $this->recursiveArrayKeyPrepend($v);
            }

            $parsed[$k] = $v;
        }

        return $parsed;
    }
}
