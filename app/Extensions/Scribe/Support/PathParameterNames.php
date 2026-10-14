<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Scribe\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Knuckles\Scribe\Extracting\Shared\UrlParamsNormalizer;
use Knuckles\Scribe\Tools\Utils;
use ReflectionFunctionAbstract;
use Throwable;

/**
 * Gives every route that shares a path the same path parameter names in the spec.
 *
 * Scribe names a parameter from the route name and the controller's type hints, so
 * `PUT users/{user:id}` (named `.update`) became `users/{id}` while `GET users/{user:id}`
 * (named `.view`) became `users/{user_id}`. OpenAPI forbids two templated paths that
 * differ only by parameter names, and code generators split them into separate paths.
 */
final class PathParameterNames
{
    /** @var array<string, list<string>>|null templated uri => parameter names */
    private static ?array $canonical = null;

    public static function normalize(Route $route, ReflectionFunctionAbstract $method): string
    {
        $uri = UrlParamsNormalizer::normalizeParameterNamesInRouteUri($route, $method);
        $names = self::canonical()[self::template($route->uri)] ?? null;
        if ($names === null) {
            return $uri;
        }

        $position = 0;

        return preg_replace_callback(
            '/\{(\w+)(\??)\}/',
            function (array $match) use ($names, &$position): string {
                return '{'.($names[$position++] ?? $match[1]).$match[2].'}';
            },
            $uri,
        ) ?? $uri;
    }

    /**
     * For each parameter position on a shared path, the name most of its routes get from
     * Scribe. Ties prefer a descriptive name over a bare `id`, then the route's own name.
     *
     * @return array<string, list<string>>
     */
    private static function canonical(): array
    {
        if (self::$canonical !== null) {
            return self::$canonical;
        }

        $candidates = [];
        foreach (Router::getRoutes()->getRoutes() as $route) {
            try {
                $method = Utils::getReflectedRouteMethod(Utils::getRouteClassAndMethodNames($route));
            } catch (Throwable) {
                continue;
            }

            $uri = UrlParamsNormalizer::normalizeParameterNamesInRouteUri($route, $method);
            preg_match_all('/\{(\w+)\??\}/', $uri, $normalized);
            preg_match_all('/\{(\w+)[^}]*\}/', $route->uri, $raw);

            foreach ($normalized[1] as $position => $name) {
                $candidates[self::template($route->uri)][$position][$name] ??= ['count' => 0, 'raw' => $raw[1][$position] ?? null];
                $candidates[self::template($route->uri)][$position][$name]['count']++;
            }
        }

        self::$canonical = [];
        foreach ($candidates as $template => $positions) {
            $chosen = [];
            foreach ($positions as $names) {
                uksort($names, fn (string $a, string $b): int => [
                    $names[$b]['count'],
                    $b !== 'id',
                    $b === $names[$b]['raw'],
                    $a,
                ] <=> [
                    $names[$a]['count'],
                    $a !== 'id',
                    $a === $names[$a]['raw'],
                    $b,
                ]);

                $chosen[] = array_key_first($names);
            }

            self::$canonical[$template] = $chosen;
        }

        return self::$canonical;
    }

    private static function template(string $uri): string
    {
        return preg_replace('/\{[^}]+\}/', '{}', mb_trim($uri, '/')) ?? $uri;
    }
}
