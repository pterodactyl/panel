<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Includes;

use Illuminate\Http\Request;
use Pterodactyl\Support\JsonValueGuard;

/**
 * The includes a request asked for, parsed as league/fractal's Manager parses them, so API
 * resources embed the same relationships Fractal transformers did. Names are compared
 * strictly where Fractal compared them loosely; that only differs for numeric names such
 * as "1" and "01", and no include has one.
 */
final readonly class IncludeSet
{
    /**
     * @param  list<string>  $requested  the include names given to the parser
     * @param  list<string>  $includes  every requested path from the root, parents included
     */
    private function __construct(private array $requested, private array $includes) {}

    /**
     * Reads `include` from the query string and the body: every value is split on commas
     * and trimmed, and empty names are dropped. Includes the controller always returns are
     * appended before parsing, and paths are cut to $limit segments.
     *
     * @param  list<string>  $forced
     */
    public static function fromRequest(Request $request, array $forced = [], int $limit = 2): self
    {
        $requested = JsonValueGuard::stringList(array_values($request->collect('include')->all()));

        $names = collect($requested)
            ->flatMap(fn (string $value): array => explode(',', $value))
            ->map(fn (string $value): string => mb_trim($value))
            ->filter()
            ->values()
            ->all();

        return self::parse([...$names, ...$forced], $limit);
    }

    /**
     * Fractal's Manager::parseIncludes(): `name:modifiers` keeps only the name, while
     * `name:modifiers.relation` also requests `name.relation`; every path is cut to
     * $limit segments and the parents of a nested path are requested with it.
     *
     * @param  list<string>  $includes
     */
    public static function parse(array $includes, int $limit = 10): self
    {
        $parsed = [];
        foreach ($includes as $include) {
            [$name, $modifiers] = array_pad(explode(':', $include, 2), 2, '');
            $relation = $modifiers !== '' && $modifiers !== '0' ? explode('.', $modifiers, 2)[1] ?? '' : '';

            $name = self::trimToLimit($name, $limit);
            if (in_array($name, $parsed, true)) {
                continue;
            }

            $parsed[] = $name;

            if ($relation !== '' && $relation !== '0') {
                $parsed[] = self::trimToLimit($name.'.'.$relation, $limit);
            }
        }

        return new self($includes, self::withParents($parsed));
    }

    /**
     * The include names given to the parser: the request's, then any forced ones.
     *
     * @return list<string>
     */
    public function requestedNames(): array
    {
        return $this->requested;
    }

    /**
     * Every requested include path, as Fractal's Manager::getRequestedIncludes() returns it.
     *
     * @return list<string>
     */
    public function includes(): array
    {
        return $this->includes;
    }

    /**
     * Whether the full dotted path from the root was requested.
     */
    public function contains(string $path): bool
    {
        return in_array($path, $this->includes, true);
    }

    public function root(): IncludeScope
    {
        return new IncludeScope($this);
    }

    private static function trimToLimit(string $include, int $limit): string
    {
        return implode('.', array_slice(explode('.', $include), 0, $limit));
    }

    /**
     * Turns [foo, bar.baz] into [foo, bar, bar.baz].
     *
     * @param  list<string>  $includes
     * @return list<string>
     */
    private static function withParents(array $includes): array
    {
        $parsed = [];
        foreach ($includes as $include) {
            $nested = explode('.', $include);

            $part = array_shift($nested);
            $parsed[] = $part;

            while ($nested !== []) {
                $part .= '.'.array_shift($nested);
                $parsed[] = $part;
            }
        }

        return array_values(array_unique($parsed));
    }
}
