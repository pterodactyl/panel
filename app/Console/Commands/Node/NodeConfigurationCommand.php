<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Models\Node;

#[Description('Displays the configuration for the specified node.')]
#[Signature('p:node:configuration
                            {node : The ID or UUID of the node to return the configuration for.}
                            {--format=yaml : The output format. Options are "yaml" and "json".}')]
class NodeConfigurationCommand extends Command
{
    public function handle(): int
    {
        $argument = $this->argument('node');
        $column = ctype_digit($argument) ? 'id' : 'uuid';

        $node = Node::query()->where($column, $argument)->first();
        if ($node === null) {
            $this->error('The selected node does not exist.');

            return 1;
        }

        $format = $this->option('format');
        if (! in_array($format, ['yaml', 'yml', 'json'])) {
            $this->error('Invalid format specified. Valid options are "yaml" and "json".');

            return 1;
        }

        if ($format === 'json') {
            $this->output->write($node->getJsonConfiguration(true));
        } else {
            $this->output->write($node->getYamlConfiguration());
        }

        $this->output->newLine();

        return 0;
    }
}
