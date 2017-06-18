<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Feature\Services;

use Tests\TestCase;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\LocationService;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Validation\ValidationException;

class LocationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Services\LocationService
     */
    protected $service;

    /**
     * Setup the test instance.
     */
    public function setUp()
    {
        parent::setUp();

        $this->service = $this->app->make(LocationService::class);
    }

    /**
     * Test that a new location can be successfully added to the database.
     */
    public function testShouldCreateANewLocation()
    {
        $data = [
            'long' => 'Long Name',
            'short' => 'short',
        ];

        $response = $this->service->create($data);

        $this->assertInstanceOf(Location::class, $response);
        $this->assertEquals($data['long'], $response->long);
        $this->assertEquals($data['short'], $response->short);
        $this->assertDatabaseHas('locations', [
            'short' => $data['short'],
            'long' => $data['long'],
        ]);
    }

    /**
     * Test that a validation error is thrown if a required field is missing.
     *
     * @expectedException \Watson\Validating\ValidationException
     */
    public function testShouldFailToCreateLocationIfMissingParameter()
    {
        $data = ['long' => 'Long Name'];

        try {
            $this->service->create($data);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(ValidationException::class, $ex);

            $bag = $ex->getMessageBag()->messages();
            $this->assertArraySubset(['short' => [0]], $bag);
            $this->assertEquals('The short field is required.', $bag['short'][0]);

            throw $ex;
        }
    }

    /**
     * Test that a validation error is thrown if the short code provided is already in use.
     *
     * @expectedException \Watson\Validating\ValidationException
     */
    public function testShouldFailToCreateLocationIfShortCodeIsAlreadyInUse()
    {
        factory(Location::class)->create(['short' => 'inuse']);
        $data = [
            'long' => 'Long Name',
            'short' => 'inuse',
        ];

        try {
            $this->service->create($data);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(ValidationException::class, $ex);

            $bag = $ex->getMessageBag()->messages();
            $this->assertArraySubset(['short' => [0]], $bag);
            $this->assertEquals('The short has already been taken.', $bag['short'][0]);

            throw $ex;
        }
    }

    /**
     * Test that a validation error is thrown if the short code is too long.
     *
     * @expectedException \Watson\Validating\ValidationException
     */
    public function testShouldFailToCreateLocationIfShortCodeIsTooLong()
    {
        $data = [
            'long' => 'Long Name',
            'short' => str_random(200),
        ];

        try {
            $this->service->create($data);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(ValidationException::class, $ex);

            $bag = $ex->getMessageBag()->messages();
            $this->assertArraySubset(['short' => [0]], $bag);
            $this->assertEquals('The short must be between 1 and 60 characters.', $bag['short'][0]);

            throw $ex;
        }
    }

    /**
     * Test that updating a model returns the updated data in a persisted form.
     */
    public function testShouldUpdateLocationModelInDatabase()
    {
        $location = factory(Location::class)->create();
        $data = ['short' => 'test_short'];

        $model = $this->service->update($location->id, $data);

        $this->assertInstanceOf(Location::class, $model);
        $this->assertEquals($data['short'], $model->short);
        $this->assertNotEquals($model->short, $location->short);
        $this->assertEquals($location->long, $model->long);
        $this->assertDatabaseHas('locations', [
            'short' => $data['short'],
            'long' => $location->long,
        ]);
    }

    /**
     * Test that passing the same short-code into the update function as the model
     * is currently using will not throw a validation exception.
     */
    public function testShouldUpdateModelWithoutErrorWhenValidatingShortCodeIsUnique()
    {
        $location = factory(Location::class)->create();
        $data = ['short' => $location->short];

        $model = $this->service->update($location->id, $data);

        $this->assertInstanceOf(Location::class, $model);
        $this->assertEquals($model->short, $location->short);

        // Timestamps don't change if no data is modified.
        $this->assertEquals($model->updated_at, $location->updated_at);
    }

    /**
     * Test that passing invalid data to the update method will throw a validation
     * exception.
     *
     * @expectedException \Watson\Validating\ValidationException
     */
    public function testShouldNotUpdateModelIfPassedDataIsInvalid()
    {
        $location = factory(Location::class)->create();
        $data = ['short' => str_random(200)];

        $this->service->update($location->id, $data);
    }

    /**
     * Test that an invalid model exception is thrown if a model doesn't exist.
     *
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testShouldThrowExceptionIfInvalidModelIdIsProvided()
    {
        $this->service->update(0, []);
    }

    /**
     * Test that a location can be deleted normally when no nodes are attached.
     */
    public function testShouldDeleteExistingLocation()
    {
        $location = factory(Location::class)->create();

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
        ]);

        $model = $this->service->delete($location);

        $this->assertTrue($model);
        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }

    /**
     * Test that a location cannot be deleted if a node is attached to it.
     *
     * @expectedException \Pterodactyl\Exceptions\DisplayException
     */
    public function testShouldFailToDeleteExistingLocationWithAttachedNodes()
    {
        $location = factory(Location::class)->create();
        $node = factory(Node::class)->create(['location_id' => $location->id]);

        $this->assertDatabaseHas('locations', ['id' => $location->id]);
        $this->assertDatabaseHas('nodes', ['id' => $node->id]);

        try {
            $this->service->delete($location->id);
        } catch (\Exception $ex) {
            $this->assertInstanceOf(DisplayException::class, $ex);
            $this->assertNotEmpty($ex->getMessage());

            throw $ex;
        }
    }
}
