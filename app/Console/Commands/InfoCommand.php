<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class InfoCommand extends Command
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var string
     */
    protected $signature = 'p:info';

    /**
     * @var \Pterodactyl\Services\Helpers\SoftwareVersionService
     */
    protected $versionService;

    /**
     * VersionCommand constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository              $config
     * @param \Pterodactyl\Services\Helpers\SoftwareVersionService $versionService
     */
    public function __construct(ConfigRepository $config, SoftwareVersionService $versionService)
    {
        parent::__construct();

        $this->config = $config;
        $this->versionService = $versionService;
    }

    /**
     * Handle execution of command.
     */
    public function handle()
    {
        $this->output->title('Version Information');
        $this->table([], [
            ['Panel Version', $this->config->get('app.version')],
            ['Latest Version', $this->versionService->getPanel()],
            ['Up-to-Date', $this->versionService->isLatestPanel() ? 'Yes' : $this->formatText('No', 'bg=red')],
            ['Unique Identifier', $this->config->get('pterodactyl.service.author')],
        ], 'compact');

        $this->output->title('Application Configuration');
        $this->table([], [
            ['Environment', $this->formatText($this->config->get('app.env'), $this->config->get('app.env') === 'production' ?: 'bg=red')],
            ['Debug Mode', $this->formatText($this->config->get('app.debug') ? 'Yes' : 'No', ! $this->config->get('app.debug') ?: 'bg=red')],
            ['Installation URL', $this->config->get('app.url')],
            ['Installation Directory', base_path()],
            ['Timezone', $this->config->get('app.timezone')],
            ['Cache Driver', $this->config->get('cache.default')],
            ['Queue Driver', $this->config->get('queue.default')],
            ['Session Driver', $this->config->get('session.driver')],
            ['Filesystem Driver', $this->config->get('filesystems.default')],
            ['Default Theme', $this->config->get('themes.active')],
            ['Proxies', $this->config->get('trustedproxies.proxies')],
        ], 'compact');

        $this->output->title('Database Configuration');
        $driver = $this->config->get('database.default');
        $this->table([], [
            ['Driver', $driver],
            ['Host', $this->config->get("database.connections.{$driver}.host")],
            ['Port', $this->config->get("database.connections.{$driver}.port")],
            ['Database', $this->config->get("database.connections.{$driver}.database")],
            ['Usernamne', $this->config->get("database.connections.{$driver}.username")],
        ], 'compact');

        $this->output->title('Email Configuration');
        $this->table([], [
            ['Driver', $this->config->get('mail.driver')],
            ['Host', $this->config->get('mail.host')],
            ['Port', $this->config->get('mail.port')],
            ['Username', $this->config->get('mail.username')],
            ['From Address', $this->config->get('mail.from.address')],
            ['From Name', $this->config->get('mail.from.name')],
            ['Encryption', $this->config->get('mail.encryption')],
        ], 'compact');
    }

    /**
     * Format output in a Name: Value manner.
     *
     * @param string $value
     * @param string $opts
     * @return string
     */
    private function formatText($value, $opts = '')
    {
        return sprintf('<%s>%s</>', $opts, $value);
    }
}
