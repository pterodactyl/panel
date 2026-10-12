<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Tags\SyncsTags;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;

final readonly class SyncTags implements SyncsTags
{
    /**
     * Rules for a tag value given as text: an existing tag's id as a numeric string, or the
     * name of a tag to find or create.
     *
     * @var list<string>
     */
    public const array TEXT = ['string', 'max:191'];

    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'tags' => ['present', 'array'],
            // A numeric value is read as an existing tag id rather than a new name.
            // \Pterodactyl\Rules\TagSlug is what keeps those two spaces from
            // overlapping, so no rule is needed here beyond the length bound.
            'tags.*' => [Rule::anyOf([['integer:strict'], self::TEXT])],
        ];
    }

    /**
     * An integer id becomes the numeric string the panel sends.
     *
     * @param  list<mixed>  $values
     * @return list<string>
     */
    public static function values(array $values): array
    {
        $input = Validator::make(['tags' => $values], self::rules())->safe();

        return array_map(fn (int|string $key): string => $input->string('tags.'.$key)->toString(), array_keys($input->array('tags')));
    }

    public function syncEgg(Egg $egg, array $values): void
    {
        $this->sync($egg, $egg->tags(), self::values($values));
    }

    public function syncNodeGames(Node $node, array $values): void
    {
        $this->sync($node, $node->eggTags(), self::values($values), $node->deploymentTags());
    }

    public function syncNodeDeployments(Node $node, array $values): void
    {
        $this->sync($node, $node->deploymentTags(), self::values($values), $node->eggTags());
    }

    /**
     * A node holds each tag either as a game or as a reservation, never both, so tags
     * already attached as the other kind are rejected rather than left to collide in
     * the pivot table.
     *
     * @template T of Egg|Node
     *
     * @param  T  $parent
     * @param  MorphToMany<Tag, T>  $tags
     * @param  list<string>  $values
     * @param  MorphToMany<Tag, T>|null  $otherKind
     */
    private function sync(Egg|Node $parent, MorphToMany $tags, array $values, ?MorphToMany $otherKind = null): void
    {
        DB::transaction(function () use ($parent, $tags, $values, $otherKind): void {
            $parent->newQuery()->lockForUpdate()->findOrFail($parent->id);
            $ids = Tag::resolveIds($values);

            $taken = $otherKind?->whereKey($ids)->pluck('tags.name')->implode(', ') ?? '';
            if ($taken !== '') {
                throw ValidationException::withMessages(['tags' => "These tags are already on the node as the other kind: {$taken}."]);
            }

            $tags->sync($ids);
        });
    }
}
