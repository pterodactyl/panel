<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;
use UnexpectedValueException;

/**
 * The document head elements extensions have registered this boot, keyed by extension
 * id. Populated by ExtensionProvider::registerHeadTags() once a provider has booted
 * successfully; rendered into the SPA layout for guests and signed-in users alike.
 *
 * A static list is validated when it is registered. A closure is resolved when the
 * layout renders and its validated result is cached, so settings-driven tags cost no
 * query on most page loads; a closure that throws or returns an invalid element is
 * recorded against its extension and contributes nothing. Rendering never throws.
 */
final class ExtensionHeadTags implements Htmlable
{
    /** @var array<string, array{version: string, tags: list<ExtensionHeadTag>|Closure}> */
    private array $sources = [];

    /** @var list<ExtensionHeadTag>|null */
    private ?array $resolved = null;

    public function __construct(private readonly ExtensionRepository $extensions) {}

    /**
     * @param  array<array-key, ApiValue9>|Closure  $tags
     *
     * @throws InvalidArgumentException|UnexpectedValueException
     */
    public function register(string $identifier, string $version, array|Closure $tags): void
    {
        $this->sources[$identifier] = ['version' => $version, 'tags' => $tags instanceof Closure ? $tags : $this->parse($tags)];
        $this->resolved = null;
    }

    /** Drop the cached result of an extension's closure so the next render resolves it again. */
    public function forget(string $identifier): void
    {
        $source = $this->sources[$identifier] ?? null;
        if ($source !== null) {
            Cache::forget($this->cacheKey($identifier, $source['version']));
        }

        $this->resolved = null;
    }

    /**
     * Whether an extension supplies its own version of one of the panel's default head
     * elements, e.g. `replaces('meta', 'theme-color')` or `replaces('link', 'manifest')`.
     */
    public function replaces(string $tag, string $key): bool
    {
        $needle = $tag.':'.$key;
        foreach ($this->tags() as $candidate) {
            if ($candidate->replaces() === $needle) {
                return true;
            }
        }

        return false;
    }

    public function toHtml(): string
    {
        return implode("\n", array_map(fn (ExtensionHeadTag $tag): string => $tag->toHtml(), $this->tags()));
    }

    /** @return array<string, array{version: string, tags: list<ExtensionHeadTag>|Closure}> */
    public function snapshot(): array
    {
        return $this->sources;
    }

    /** @param array<string, array{version: string, tags: list<ExtensionHeadTag>|Closure}> $sources */
    public function restore(array $sources): void
    {
        $this->sources = $sources;
        $this->resolved = null;
    }

    /**
     * @param  array<array-key, ApiValue9>  $tags
     * @return list<ExtensionHeadTag>
     *
     * @throws InvalidArgumentException|UnexpectedValueException
     */
    private function parse(array $tags): array
    {
        throw_if(count($tags) > ExtensionHeadTag::LIMIT, InvalidArgumentException::class, sprintf('An extension may register at most %d head tags.', ExtensionHeadTag::LIMIT));

        $parsed = [];
        foreach ($tags as $tag) {
            $parsed[] = ExtensionHeadTag::fromArray(JsonValueGuard::jsonArray($tag));
        }

        return $parsed;
    }

    private function cacheKey(string $identifier, string $version): string
    {
        return 'extensions:head:'.$identifier.':'.$version;
    }

    /**
     * Every available extension's elements, the first registration of an element winning.
     *
     * @return list<ExtensionHeadTag>
     */
    private function tags(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $tags = [];
        foreach ($this->sources as $identifier => $source) {
            try {
                if (! $this->extensions->isAvailable($identifier)) {
                    continue;
                }

                foreach ($source['tags'] instanceof Closure ? $this->resolve($identifier, $source['version'], $source['tags']) : $source['tags'] as $tag) {
                    $tags[$tag->identity()] ??= $tag;
                }
            } catch (Throwable $throwable) {
                rescue(fn () => $this->extensions->recordFailure($identifier, $throwable->getMessage(), $throwable, 'head'), report: false);
            }
        }

        return $this->resolved = array_values($tags);
    }

    /**
     * @return list<ExtensionHeadTag>
     */
    private function resolve(string $identifier, string $version, Closure $tags): array
    {
        $key = $this->cacheKey($identifier, $version);
        $seconds = JsonValueGuard::integer(config('extensions.head_tags_cache_seconds'));
        $cached = $seconds > 0 ? Cache::get($key) : null;
        if ($cached !== null) {
            return $this->parse(JsonValueGuard::jsonArray($cached));
        }

        try {
            $parsed = $this->parse(JsonValueGuard::jsonArray($tags()));
        } catch (Throwable $throwable) {
            // Remember the failure too, or a broken callback would run on every page load.
            $this->remember($key, $seconds, []);

            throw $throwable;
        }

        $this->remember($key, $seconds, array_map(fn (ExtensionHeadTag $tag): array => ['tag' => $tag->tag, ...$tag->attributes], $parsed));

        return $parsed;
    }

    /** @param list<array<string, string>> $tags */
    private function remember(string $key, int $seconds, array $tags): void
    {
        if ($seconds > 0) {
            Cache::put($key, $tags, $seconds);
        }
    }
}
