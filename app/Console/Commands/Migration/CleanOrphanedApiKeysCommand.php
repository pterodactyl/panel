<?php

namespace App\Console\Commands\Migration;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use App\Contracts\Repository\ApiKeyRepositoryInterface;

class CleanOrphanedApiKeysCommand extends Command
{
    /**
     * @var \App\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * @var string
     */
    protected $signature = 'p:migration:clean-orphaned-keys';

    /**
     * @var string
     */
    protected $description = 'Cleans API keys from the database that are not assigned a specific role.';

    /**
     * CleanOrphanedApiKeysCommand constructor.
     *
     * @param \App\Contracts\Repository\ApiKeyRepositoryInterface $repository
     */
    public function __construct(ApiKeyRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Delete all orphaned API keys from the database when upgrading from 0.6 to 0.7.
     *
     * @return null|void
     */
    public function handle()
    {
        $count = $this->repository->findCountWhere([['key_type', '=', ApiKey::TYPE_NONE]]);
        $continue = $this->confirm(
            'This action will remove ' . $count . ' keys from the database. Are you sure you wish to continue?', false
        );

        if (! $continue) {
            return null;
        }

        $this->info('Deleting keys...');
        $this->repository->deleteWhere([['key_type', '=', ApiKey::TYPE_NONE]]);
        $this->info('Keys were successfully deleted.');
    }
}
