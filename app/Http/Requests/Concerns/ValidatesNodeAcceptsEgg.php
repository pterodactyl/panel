<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Concerns;

use Illuminate\Support\Str;
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

        $replacements = [];
        foreach (['node' => $node->name, 'tags' => $node->eggTags->map(fn (Tag $tag): string => $tag->effectiveName())->implode(', ')] as $key => $value) {
            $replacements[':'.$key] = $value;
            $replacements[':'.Str::ucfirst($key)] = Str::ucfirst($value);
            $replacements[':'.Str::upper($key)] = Str::upper($value);
        }

        $validator->addReplacer('node_accepts_egg', fn (string $message): string => strtr($message, $replacements));

        $validator->addFailure($field, 'NodeAcceptsEgg');
    }
}
