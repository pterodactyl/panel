<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Concerns;

use Illuminate\Validation\Validator;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;

/**
 * Lets an Admin API request that places a server on a node refuse a node that does not
 * accept the egg, unless `ignore_tags` is set. Only the egg half of the deploy gate is
 * checked: servers do not keep the deploy tags that a node's reservations match.
 */
trait ValidatesNodeAcceptsEgg
{
    protected function validateNodeAcceptsEgg(Validator $validator, string $field, Node $node, Egg $egg): void
    {
        if ($this->boolean('ignore_tags') || $node->acceptsEgg($egg)) {
            return;
        }

        $tags = $node->eggTags->map(fn (Tag $tag): string => $tag->effectiveName())->implode(', ');

        $validator->errors()->add($field, "{$node->name} only accepts eggs tagged {$tags}.");
    }
}
