<?php

namespace Pterodactyl\Tests\Traits;

use Mockery as m;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

trait MocksUuids
{
    /**
     * The known UUID string.
     */
    protected string $knownUuid = 'ffb5c3a6-ab17-43ab-97f0-8ff37ccd7f5f';

    /**
     * Setup a factory mock to produce the same UUID whenever called.
     */
    public function setKnownUuidFactory(): void
    {
        $uuid = Uuid::fromString($this->getKnownUuid());
        $factoryMock = m::mock(UuidFactory::class . '[uuid4]', [
            'uuid4' => $uuid,
        ]);

        Uuid::setFactory($factoryMock);
    }

    /**
     * Returns the known UUID for tests to use.
     */
    public function getKnownUuid(): string
    {
        return $this->knownUuid;
    }
}
