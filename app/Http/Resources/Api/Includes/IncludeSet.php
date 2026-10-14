<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Includes;

use Illuminate\Http\Request;
use UnexpectedValueException;

final readonly class IncludeSet
{
    /**
     * @param  list<string>  $requested  the include names given to the parser
     * @param  list<string>  $includes  every requested path from the root, parents included
     */
    private function __construct(private array $requested, private array $includes) {}

    /**
     * @param  list<string>  $forced
     */
    public static function fromRequest(Request $request, array $forced = [], int $limit = 2): self
    {
        $names = $request->collect('include')
            ->map(fn (mixed $value): string => is_string($value) ? $value : throw new UnexpectedValueException('The requested includes must be strings.'))
            ->flatMap(fn (string $value): array => explode(',', $value))
            ->map(fn (string $value): string => mb_trim($value))
            ->filter()
            ->values()
            ->all();

        return self::parse([...$names, ...$forced], $limit);
    }

    /**
     * `name:modifiers` keeps only the name, while `name:modifiers.relation` also requests
     * `name.relation`.
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
     * @return list<string>
     */
    public function requestedNames(): array
    {
        return $this->requested;
    }

    /**
     * @return list<string>
     */
    public function includes(): array
    {
        return $this->includes;
    }

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
