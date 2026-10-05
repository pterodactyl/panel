<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use JsonException;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UnexpectedValueException;

final readonly class EggCatalogEntry
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $category,
        private string $downloadUrl,
    ) {}

    /** @return list<self> */
    public static function listFromJson(string $contents): array
    {
        try {
            $data = JsonValueGuard::decodeArray8($contents);
        } catch (JsonException|UnexpectedValueException $exception) {
            throw new HttpException(502, 'Pterodactyl returned an invalid egg catalog. Please try again later.', $exception);
        }

        throw_unless(array_is_list($data), HttpException::class, 502, 'Pterodactyl returned an invalid egg catalog. Please try again later.');

        $entries = [];
        foreach ($data as $value) {
            $entry = self::fromValue($value);
            if ($entry instanceof self) {
                $entries[] = $entry;
            }
        }

        throw_if($data !== [] && $entries === [], HttpException::class, 502, 'Pterodactyl returned an invalid egg catalog. Please try again later.');

        return $entries;
    }

    /** @param JsonInputValue $value */
    public static function fromValue(mixed $value): ?self
    {
        if (! is_array($value) || ! is_string($value['id'] ?? null) || ! is_string($value['name'] ?? null)
            || ! is_string($value['downloadUrl'] ?? null) || $value['id'] === '' || $value['name'] === '') {
            return null;
        }

        return new self(
            $value['id'],
            $value['name'],
            is_string($value['description'] ?? null) ? $value['description'] : '',
            is_string($value['category'] ?? null) ? $value['category'] : '',
            $value['downloadUrl'],
        );
    }

    public function downloadUrl(): string
    {
        try {
            $uri = new Uri($this->downloadUrl);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new HttpException(502, 'The catalog egg has an invalid download URL.', $invalidArgumentException);
        }

        throw_if($uri->getScheme() !== 'https' || $uri->getHost() !== 'raw.githubusercontent.com'
            || ! str_starts_with($uri->getPath(), '/pterodactyl/') || $uri->getUserInfo() !== ''
            || $uri->getPort() !== null || $uri->getQuery() !== '' || $uri->getFragment() !== '', HttpException::class, 502, 'The catalog egg does not have an official Pterodactyl download URL.');

        return $uri->__toString();
    }

    /** @return array{id: string, name: string, description: string, category: string, source_url: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'source_url' => 'https://eggs.pterodactyl.io/egg/'.rawurlencode($this->id),
        ];
    }
}
