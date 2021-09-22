<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Node;

use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\ApiKey;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Repositories\Eloquent\ApiKeyRepository;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class GenerateAutoDeployTokenCommand extends Command
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Api\KeyCreationService
     */
    private $keyCreationService;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ApiKeyRepository
     */
    private $apiKeyRepository;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var string
     */
    protected $signature = 'p:node:autodeploy-token
                            {--user= : Admin Email address.}
                            {--node= : Node name.}';

    /**
     * @var string
     */
    protected $description = 'Generate token with url for auto-deploying via the CLI.';

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $nodes;

    /**
     * Create a new command instance.
     */
    public function __construct(NodeRepositoryInterface $repository, ApiKeyRepository $apiKeyRepository, Encrypter $encrypter, KeyCreationService $keyCreationService,
                                ConfigRepository $config)
    {
        parent::__construct();

        $this->repository = $repository;
        $this->apiKeyRepository = $apiKeyRepository;
        $this->encrypter = $encrypter;
        $this->keyCreationService = $keyCreationService;
        $this->config = $config;
    }

    /**
     * Handle the command execution process.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle()
    {
        $search = $this->option('user') ?? $this->ask(trans('command/messages.node.search_users'));
        Assert::notEmpty($search, 'Search term should be an email address, got: %s.');

        $results = User::query()
            ->where('root_admin', '=', true)
            ->orWhere('email', '=', "$search")
            ->get();

        if (count($results) < 1) {
            $this->error(trans('command/messages.user.no_users_found'));
            if ($this->input->isInteractive()) {
                return $this->handle();
            }

            return false;
        }

        $choiceUser = $results->first();
        $this->nodes = $this->nodes ?? $this->repository->all();
        $node_name = $this->option('node') ?? $this->anticipate(trans('command/messages.node.ask_name'), $this->nodes->pluck('name')->toArray());
        $node = $this->nodes->where('name', $node_name)->first();

        if (is_null($node)) {
            $this->error(trans('command/messages.node.no_node_found'));
            if ($this->input->isInteractive()) {
                $this->handle();
            }

            return;
        }

        $token = $this->generateToken($choiceUser);
        $app_url = $this->config->get('app.url', 'http://localhost');

        $this->info("cd /etc/pterodactyl && sudo wings configure --panel-url {$app_url} --token {$token} --node {$node->id}");
    }

    public function generateToken(User $user): string
    {
        /** @var \Pterodactyl\Models\ApiKey|null $key */
        $key = $this->apiKeyRepository->getApplicationKeys($user)
            ->filter(function (ApiKey $key) {
                foreach ($key->getAttributes() as $permission => $value) {
                    if ($permission === 'r_nodes' && $value === 1) {
                        return true;
                    }
                }

                return false;
            })
            ->first();

        if (!$key) {
            $key = $this->keyCreationService->setKeyType(ApiKey::TYPE_APPLICATION)->handle([
                'user_id' => $user->id,
                'memo' => 'Generated node deployment key from CLI.',
                'allowed_ips' => [],
            ], ['r_nodes' => 1]);
        }

        return $key->identifier . $this->encrypter->decrypt($key->token);
    }
}
