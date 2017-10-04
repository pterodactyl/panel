<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Services\Exporter;

use Closure;
use Carbon\Carbon;
use Sabre\Xml\Writer;
use Sabre\Xml\Service;
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
     * @param int $option
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $option): string
    {
        $option = $this->repository->getWithCopyAttributes($option);

        $struct = [
            'meta' => [
                'version' => 'PTDL_v1',
            ],
            'exported_at' => $this->carbon->now()->toIso8601String(),
            'name' => $option->name,
            'author' => array_get(explode(':', $option->tag), 0),
            'tag' => $option->tag,
            'description' => $this->writeCData($option->description),
            'image' => $option->docker_image,
            'config' => [
                'files' => $this->writeCData($option->inherit_config_files),
                'startup' => $this->writeCData($option->inherit_config_startup),
                'logs' => $this->writeCData($option->inherit_config_logs),
                'stop' => $option->inherit_config_stop,
            ],
            'scripts' => [
                'installation' => [
                    'script' => $this->writeCData($option->copy_script_install),
                    'container' => $option->copy_script_container,
                    'entrypoint' => $option->copy_script_entry,
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

    /**
     * Return a closure to be used by the XML writer to generate a string wrapped in CDATA tags.
     *
     * @param string $value
     * @return \Closure
     */
    protected function writeCData(string $value): Closure
    {
        return function (Writer $writer) use ($value) {
            return $writer->writeCData($value);
        };
    }
}
