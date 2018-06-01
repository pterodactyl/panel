<?php

namespace Pterodactyl\Tests\Integration;

use Tests\TestCase;
use Cake\Chronos\Chronos;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Transformers\Api\Application\BaseTransformer;

abstract class IntegrationTestCase extends TestCase
{
    /**
     * Setup base integration test cases.
     */
    public function setUp()
    {
        parent::setUp();

        // Disable event dispatcher to prevent eloquence from trying to
        // perform validation on models going into the database. If this is
        // not disabled, eloquence validation errors get swallowed and
        // the tests cannot complete because nothing is put into the database.
        Model::unsetEventDispatcher();
    }

    /**
     * @return array
     */
    protected function connectionsToTransact()
    {
        return ['testing'];
    }

    /**
     * Return an ISO-8601 formatted timestamp to use in the API response.
     *
     * @param string $timestamp
     * @return string
     */
    protected function formatTimestamp(string $timestamp): string
    {
        return Chronos::createFromFormat(Chronos::DEFAULT_TO_STRING_FORMAT, $timestamp)
            ->setTimezone(BaseTransformer::RESPONSE_TIMEZONE)
            ->toIso8601String();
    }
}
