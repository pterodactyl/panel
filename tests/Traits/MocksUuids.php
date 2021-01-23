<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Tests\Traits;

use Mockery as m;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

trait MocksUuids
{
    /**
     * The known UUID string.
     *
     * @var string
     */
    protected $knownUuid = 'ffb5c3a6-ab17-43ab-97f0-8ff37ccd7f5f';

    /**
     * Setup a factory mock to produce the same UUID whenever called.
     */
    public function setKnownUuidFactory()
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
