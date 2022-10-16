<?php

namespace Pterodactyl\Console\Commands\Node;

use Pterodactyl\Models\Node;
use Illuminate\Console\Command;

class NodeListCommand extends Command
{
    protected $signature = 'p:node:list {--format=text : The output format: "text" or "json". }';

    public function handle(): int
    {
        $nodes = Node::query()->with('location')->get()->map(function (Node $node) {
            return [
                'id' => $node->id,
                'uuid' => $node->uuid,
                'name' => $node->name,
                'location' => $node->location->short,
                'host' => $node->getConnectionAddress(),
            ];
        });

        if ($this->option('format') === 'json') {
            $this->output->write($nodes->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['ID', 'UUID', 'Name', 'Location', 'Host'], $nodes->toArray());
        }

        $this->output->newLine();

        return 0;
    }
}
