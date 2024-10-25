<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use RuntimeException;

use function __;

final class FlashMessages
{
    private const STORAGE_KEY = 'flashMessages';

    /** @var array */
    private $storage;

    /** @var array<string, string[]> */
    private $previousMessages = [];

    public function __construct()
    {
        if (! isset($_SESSION)) {
            throw new RuntimeException(__('Session not found.'));
        }

        $this->storage = &$_SESSION;

        if (isset($this->storage[self::STORAGE_KEY])) {
            $this->previousMessages = $this->storage[self::STORAGE_KEY];
        }

        $this->storage[self::STORAGE_KEY] = [];
    }

    public function addMessage(string $key, string $message): void
    {
        if (! isset($this->storage[self::STORAGE_KEY][$key])) {
            $this->storage[self::STORAGE_KEY][$key] = [];
        }

        $this->storage[self::STORAGE_KEY][$key][] = $message;
    }

    /** @return string[]|null */
    public function getMessage(string $key): ?array
    {
        return $this->previousMessages[$key] ?? null;
    }

    /** @return array<string, string[]> */
    public function getMessages(): array
    {
        return $this->previousMessages;
    }
}
