<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use UnexpectedValueException;

final class RemoteRequestNode
{
    public static function get(Request $request): Node
    {
        $node = $request->attributes->get('node');
        throw_unless($node instanceof Node, UnexpectedValueException::class, 'The authenticated node is missing from the remote request.');

        return $node;
    }
}
