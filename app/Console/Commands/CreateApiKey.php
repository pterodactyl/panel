<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Pterodactyl\Console\Kernel;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Api\KeyCreationService;

class CreateApiKey extends Command
{

    public function __construct(
        private KeyCreationService $apiKeyCreationService
    ) {
        parent::__consrtuct();
    }

    protected $signature = 'p:panel-api:create-key
        {--username= : The username for which to create an API key for}
        {--password= : The password for the user account.}
        {--description= : The description to assign to the API key.}
        {--file_output= : The location of the file output for the API key (outputs the API key to stdout by default).}
        {--allocations=[r, rw]: API permissions for reading and writing allocations.}
        {--database_hosts=[r, rw]: API permissions for reading and writing database hosts.}
        {--eggs=[r, rw]: API permissions for reading and writing eggs.}
        {--locations=[r, rw]: API permissions for reading and writing locations.}
        {--nests=[r, rw]: API permissions for reading and writing nests.}
        {--nodes=[r, rw]: API permissions for reading and writing nodes.}
        {--server_databases=[r, rw]: API permissions for reading and writing server databases.}
        {--servers=[r, rw]: API permissions for reading and writing servers.}
        {--users=[r, rw]: API permissions for reading and writing users.}';

    protected $description = 'Allows the creation of a new API key for the Panel.';

    /**
     * Execute a command to create a new API key for the Panel for the specified user.
     * 
     * @throws \Pterodactyl\Exceptions\Model\ModelNotFoundException
     */
    public function handle(): void
    {
        $username = $this->option('username') ?? $this->ask(trans('command/message.user.ask_username'));
        $password = $this->option('password') ?? $this->secret(trans('command/message.user.ask_password'));
        $description = $this->option('description') ?? $this->ask(trans('command/message.API_key.ask_API_key_description'));

        $permissions = [
            'r_allocations'         => $this->option('allocations')         === 'rw' ? 3 : ($this->option('allocations')         === 'r' ? 2 : 1),
            'r_database_hosts'      => $this->option('database_hosts')      === 'rw' ? 3 : ($this->option('database_hosts')      === 'r' ? 2 : 1),
            'r_eggs'                => $this->option('eggs')                === 'rw' ? 3 : ($this->option('eggs')                === 'r' ? 2 : 1),
            'r_locations'           => $this->option('locations')           === 'rw' ? 3 : ($this->option('locations')           === 'r' ? 2 : 1),
            'r_nests'               => $this->option('nests')               === 'rw' ? 3 : ($this->option('nests')               === 'r' ? 2 : 1),
            'r_nodes'               => $this->option('nodes')               === 'rw' ? 3 : ($this->option('nodes')               === 'r' ? 2 : 1),
            'r_server_databases'    => $this->option('server_databases')    === 'rw' ? 3 : ($this->option('server_databases')    === 'r' ? 2 : 1),
            'r_servers'             => $this->option('servers')             === 'rw' ? 3 : ($this->option('servers')             === 'r' ? 2 : 1),
            'r_users'               => $this->option('users')               === 'rw' ? 3 : ($this->option('users')               === 'r' ? 2 : 1)
        ];

        try {
            $user = User::query()->where($this->getField($username), $username)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $this->line(trans('command/message.user.error_user_auth_invalid'));
            throw $e;
        }

        $apiKeyCreationService->setKeyType(ApiKey::TYPE_APPLICATION);

        $apiKeyCreated = $apiKeyCreationService->handle(compact('username', 'password', 'description'), );

        $this->table(['Field', 'Value'], [
            ['Username', $apiKeyCreated->username],
            ['']
        ]);
    }

    /**
     * Determine if the user is logging in using an email or username.
     */
    protected function getField(string $input = null): string
    {
        return ($input && str_contains($input, '@')) ? 'email' : 'username';
    }
}
