<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Models\Node;

#[Signature('p:node:list {--format=text : The output format: "text" or "json". }')]
class NodeListCommand extends Command
{
    public function handle(): int
    {
        $nodes = Node::query()->with('location')->get()->map(fn (Node $node): array => [
            'id' => $node->id,
            'uuid' => $node->uuid,
            'name' => $node->name,
            'location' => $node->location->short,
            'host' => $node->getConnectionAddress(),
        ]);

        if ($this->option('format') === 'json') {
            $this->output->write($nodes->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['ID', 'UUID', 'Name', 'Location', 'Host'], $nodes->all());
        }

        $this->output->newLine();

        return 0;
    }
}
