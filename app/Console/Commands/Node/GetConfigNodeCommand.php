<?php
namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Command;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class GetConfigNodeCommand extends Command
{

    /**
     * @var string
     */
    protected $description = 'Get a node configuration from the Panel.';

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $nodes;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var string
     */
    protected $signature = 'p:node:config {--id= : The id of the node.}';

    /**
     * GetConfigNodeCommand constructor.
     */
    public function __construct(
        NodeRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Respond to the command request.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle()
    {
        $this->nodes = $this->nodes ?? $this->repository->all();
        $id = $this->option('id') ?? $this->anticipate(
            "Enter a valid id",
            $this->nodes->pluck('id')->toArray()
        );

        $node = $this->nodes->where('id', $id)->first();
        if (is_null($node)) {
            $this->error("Node not found");
            if ($this->input->isInteractive()) {
                $this->handle();
            }

            return;
        }

        $yaml = $node->getYamlConfiguration();
        $this->line($yaml);
    }
}
