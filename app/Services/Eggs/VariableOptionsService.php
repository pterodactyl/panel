<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Eggs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Models\EggVariable;
use Throwable;
use UnexpectedValueException;

final class VariableOptionsService
{
    private const int MAX_RESPONSE_BYTES = 1024 * 1024;

    private const int MAX_OPTIONS = 500;

    /**
     * Resolve and cache an egg variable's remote suggestions. A failed source returns no
     * suggestions so the startup form remains usable through its manual input fallback.
     *
     * @return list<array{label: string, value: string}>
     */
    public function resolve(EggVariable $variable): array
    {
        $source = $variable->options_source;
        if (! is_array($source)) {
            return [];
        }

        $cacheKey = sprintf(
            'egg-variable-options:%s',
            hash('sha256', json_encode($source, JSON_THROW_ON_ERROR)),
        );

        return Cache::remember($cacheKey, now()->addMinutes(15), fn (): array => $this->fetch($source));
    }

    /**
     * @param  array<string, mixed>  $source
     * @return list<array{label: string, value: string}>
     */
    private function fetch(array $source): array
    {
        try {
            $url = Arr::string($source, 'url');
            $resolve = $this->resolvePublicHost($url);
            $request = Http::acceptJson()->connectTimeout(3)->timeout(8)->withoutRedirecting();

            if ($resolve !== null && defined('CURLOPT_RESOLVE')) {
                $request = $request->withOptions(['curl' => [CURLOPT_RESOLVE => [$resolve]]]);
            }

            $response = $request->get($url);
            if (! $response->successful()) {
                return [];
            }

            $body = $response->body();
            if (mb_strlen($body, '8bit') > self::MAX_RESPONSE_BYTES) {
                return [];
            }

            return $this->optionsFromPayload($response->json(), $source);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Reject local and reserved targets, and pin DNS names to the address that was checked.
     *
     * @return string|null A CURLOPT_RESOLVE entry, or null for an already-public IP literal.
     */
    private function resolvePublicHost(string $url): ?string
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;
        $port = is_array($parts) ? ($parts['port'] ?? 443) : 443;

        $isInvalidUrl = ($parts['scheme'] ?? null) !== 'https'
            || ! is_string($host)
            || $host === ''
            || $port !== 443
            || isset($parts['user'])
            || isset($parts['pass']);
        if ($isInvalidUrl) {
            throw new UnexpectedValueException('Only public HTTPS option sources are supported.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            throw_unless(
                $this->isPublicIp($host),
                UnexpectedValueException::class,
                'Private option source addresses are not supported.',
            );

            return null;
        }

        $records = @dns_get_record($host, DNS_A);
        $addresses = is_array($records) ? array_column($records, 'ip') : [];
        $address = Arr::first(
            $addresses,
            fn (mixed $ip): bool => is_string($ip) && $this->isPublicIp($ip),
        );
        throw_unless(
            is_string($address),
            UnexpectedValueException::class,
            'The option source hostname has no public address.',
        );

        return sprintf('%s:443:%s', $host, $address);
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Transform common API response shapes into unique label/value suggestions.
     *
     * @param  array<string, mixed>|list<mixed>|null  $payload
     * @param  array<string, mixed>  $source
     * @return list<array{label: string, value: string}>
     */
    public function optionsFromPayload(?array $payload, array $source): array
    {
        if ($payload === null) {
            return [];
        }

        $data = data_get($payload, Arr::string($source, 'data_path'));
        $rows = $this->rows($data);
        $filters = Arr::array($source, 'filters', []);
        $valuePath = Arr::string($source, 'value_path');
        $labelPath = Arr::string($source, 'label_path', $valuePath);
        $stripSuffix = Arr::string($source, 'strip_suffix', '');
        $options = [];

        foreach ($rows as $row) {
            if (! $this->passesFilters($row, $filters)) {
                continue;
            }

            $value = $this->stringValue($row, $valuePath);
            $label = $this->stringValue($row, $labelPath);
            if ($stripSuffix !== '') {
                $value = $this->withoutSuffix($value, $stripSuffix);
                $label = $this->withoutSuffix($label, $stripSuffix);
            }
            if ($value === null || $label === null) {
                continue;
            }
            if (array_key_exists($value, $options)) {
                continue;
            }

            $options[$value] = [
                'label' => $label,
                'value' => $value,
            ];
            if (count($options) === self::MAX_OPTIONS) {
                break;
            }
        }

        return array_values($options);
    }

    private function withoutSuffix(?string $value, string $suffix): ?string
    {
        if ($value === null || ! str_ends_with($value, $suffix)) {
            return $value;
        }

        return mb_substr($value, 0, -mb_strlen($suffix));
    }

    /** @return list<array{key: string|int|null, value: mixed}> */
    private function rows(mixed $value): array
    {
        if (! is_array($value)) {
            return [['key' => null, 'value' => $value]];
        }

        if (! array_is_list($value)) {
            return array_map(
                fn (string|int $key, mixed $item): array => ['key' => $key, 'value' => $item],
                array_keys($value),
                array_values($value),
            );
        }

        $rows = [];
        foreach ($value as $item) {
            if (! is_array($item) || ! array_is_list($item)) {
                $rows[] = ['key' => null, 'value' => $item];

                continue;
            }

            $rows = [...$rows, ...$this->rows($item)];
        }

        return $rows;
    }

    /**
     * @param  array{key: string|int|null, value: mixed}  $row
     * @param  list<mixed>  $filters
     */
    private function passesFilters(array $row, array $filters): bool
    {
        foreach ($filters as $filter) {
            if (! is_array($filter)) {
                return false;
            }

            $actual = $this->stringValue($row, Arr::string($filter, 'path'));
            $expected = Arr::string($filter, 'value');
            $operator = Arr::string($filter, 'operator');

            if ($actual === null) {
                return false;
            }
            if (! $this->compare($actual, $operator, $expected)) {
                return false;
            }
        }

        return true;
    }

    private function compare(string $actual, string $operator, string $expected): bool
    {
        return match ($operator) {
            'equals' => $actual === $expected,
            'not_equals' => $actual !== $expected,
            'contains' => str_contains($actual, $expected),
            'not_contains' => ! str_contains($actual, $expected),
            'starts_with' => str_starts_with($actual, $expected),
            'ends_with' => str_ends_with($actual, $expected),
            default => false,
        };
    }

    /** @param array{key: string|int|null, value: mixed} $row */
    private function stringValue(array $row, string $path): ?string
    {
        $value = match ($path) {
            '$key' => $row['key'],
            '$value' => $row['value'],
            default => data_get($row['value'], $path),
        };

        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : null;
    }
}
