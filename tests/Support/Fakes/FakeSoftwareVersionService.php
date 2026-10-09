<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class FakeSoftwareVersionService extends SoftwareVersionService
{
    public function __construct(
        private string $panel = '1.0.0',
        private string $daemon = '1.0.0',
        private string $discord = 'https://pterodactyl.io/discord',
        private string $donations = 'https://github.com/sponsors/matthewpi',
        private bool $isLatestPanel = true,
    ) {
        parent::__construct(
            new Repository(new ArrayStore),
            new Client(['handler' => HandlerStack::create(new MockHandler)])
        );
    }

    public function getPanel(): string
    {
        return $this->panel;
    }

    public function getDaemon(): string
    {
        return $this->daemon;
    }

    public function getDiscord(): string
    {
        return $this->discord;
    }

    public function getDonations(): string
    {
        return $this->donations;
    }

    public function isLatestPanel(): bool
    {
        return $this->isLatestPanel;
    }
}
