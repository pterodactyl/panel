<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Support\JsonValueGuard;

#[Description('Displays the application, database, and email configurations along with the panel version.')]
#[Signature('p:info')]
class InfoCommand extends Command
{
    public function __construct(private readonly SoftwareVersionService $versionService)
    {
        parent::__construct();
    }

    /**
     * Handle execution of command.
     */
    public function handle(): void
    {
        $this->output->title('Version Information');
        $this->table([], [
            ['Panel Version', config('app.version')],
            ['Latest Version', $this->versionService->getPanel()],
            ['Up-to-Date', $this->versionService->isLatestPanel() ? 'Yes' : $this->formatText('No', 'bg=red')],
            ['Unique Identifier', config('pterodactyl.service.author')],
        ], 'compact');

        $this->output->title('Application Configuration');
        $this->table([], [
            ['Environment', $this->formatText(JsonValueGuard::string(config('app.env')), config('app.env') === 'production' ? '' : 'bg=red')],
            ['Debug Mode', $this->formatText(config('app.debug') ? 'Yes' : 'No', config('app.debug') ? 'bg=red' : '')],
            ['Installation URL', config('app.url')],
            ['Installation Directory', base_path()],
            ['Timezone', config('app.timezone')],
            ['Cache Driver', config('cache.default')],
            ['Queue Driver', config('queue.default')],
            ['Session Driver', config('session.driver')],
            ['Filesystem Driver', config('filesystems.default')],
            ['Default Theme', config('themes.active')],
            ['Proxies', config('trustedproxies.proxies')],
        ], 'compact');

        $this->output->title('Database Configuration');
        $driver = JsonValueGuard::string(config('database.default'));
        $this->table([], [
            ['Driver', $driver],
            ['Host', config("database.connections.$driver.host")],
            ['Port', config("database.connections.$driver.port")],
            ['Database', config("database.connections.$driver.database")],
            ['Username', config("database.connections.$driver.username")],
        ], 'compact');

        // TODO: Update this to handle other mail drivers
        $this->output->title('Email Configuration');
        $this->table([], [
            ['Driver', config('mail.default')],
            ['Host', config('mail.mailers.smtp.host')],
            ['Port', config('mail.mailers.smtp.port')],
            ['Username', config('mail.mailers.smtp.username')],
            ['From Address', config('mail.from.address')],
            ['From Name', config('mail.from.name')],
            ['Encryption', config('mail.mailers.smtp.encryption')],
        ], 'compact');
    }

    /**
     * Format output in a Name: Value manner.
     */
    private function formatText(string $value, string $opts = ''): string
    {
        if ($opts === '') {
            return $value;
        }

        return sprintf('<%s>%s</>', $opts, $value);
    }
}
