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

namespace Tests\Unit\Services\Users;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class UserDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService
     */
    protected $service;

    /**
     * @var User
     */
    protected $user;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->user = factory(User::class)->make();
        $this->repository = m::mock(UserRepositoryInterface::class);
        $this->translator = m::mock(Translator::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);

        $this->service = new UserDeletionService(
            $this->serverRepository,
            $this->translator,
            $this->repository
        );
    }

    /**
     * Test that a user is deleted if they have no servers.
     */
    public function testUserIsDeletedIfNoServersAreAttachedToAccount()
    {
        $this->serverRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['owner_id', '=', $this->user->id]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with($this->user->id)->once()->andReturn(true);

        $this->assertTrue(
            $this->service->handle($this->user->id),
            'Assert that service responds true.'
        );
    }

    /**
     * Test that an exception is thrown if trying to delete a user with servers.
     *
     * @expectedException \Pterodactyl\Exceptions\DisplayException
     */
    public function testExceptionIsThrownIfServersAreAttachedToAccount()
    {
        $this->serverRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['owner_id', '=', $this->user->id]])->once()->andReturn(1);
        $this->translator->shouldReceive('trans')->with('admin/user.exceptions.user_has_servers')->once()->andReturnNull();

        $this->service->handle($this->user->id);
    }

    /**
     * Test that the function supports passing in a model or an ID.
     */
    public function testModelCanBePassedInPlaceOfUserId()
    {
        $this->serverRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['owner_id', '=', $this->user->id]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with($this->user->id)->once()->andReturn(true);

        $this->assertTrue(
            $this->service->handle($this->user),
            'Assert that service responds true.'
        );
    }
}
