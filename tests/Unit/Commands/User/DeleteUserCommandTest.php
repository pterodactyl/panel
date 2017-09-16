<?php
/*
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

namespace Tests\Unit\Commands\User;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Tests\Assertions\CommandAssertionsTrait;
use Pterodactyl\Services\Users\UserDeletionService;
use Symfony\Component\Console\Tester\CommandTester;
use Pterodactyl\Console\Commands\User\DeleteUserCommand;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class DeleteUserCommandTest extends TestCase
{
    use CommandAssertionsTrait;

    /**
     * @var \Pterodactyl\Console\Commands\User\DeleteUserCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->deletionService = m::mock(UserDeletionService::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->command = new DeleteUserCommand($this->deletionService, $this->repository);
        $this->command->setLaravel($this->app);
    }

    /**
     * Test that a user can be deleted using a normal pathway.
     */
    public function testCommandWithNoOptions()
    {
        $users = collect([
            $user1 = factory(User::class)->make(),
            $user2 = factory(User::class)->make(),
        ]);

        $this->repository->shouldReceive('search')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1->id)->once()->andReturnNull();

        $response = new CommandTester($this->command);
        $response->setInputs([$user1->username, $user1->id, 'yes']);
        $response->execute([]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertTableContains($user1->id, $display);
        $this->assertTableContains($user1->email, $display);
        $this->assertTableContains($user1->name, $display);
        $this->assertContains(trans('command/messages.user.deleted'), $display);
    }

    /**
     * Test a bad first user search followed by a good second search.
     */
    public function testCommandWithInvalidInitialSearch()
    {
        $users = collect([
            $user1 = factory(User::class)->make(),
        ]);

        $this->repository->shouldReceive('search')->with('noResults')->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn([]);
        $this->repository->shouldReceive('search')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1->id)->once()->andReturnNull();

        $response = new CommandTester($this->command);
        $response->setInputs(['noResults', $user1->username, $user1->id, 'yes']);
        $response->execute([]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.no_users_found'), $display);
        $this->assertTableContains($user1->id, $display);
        $this->assertTableContains($user1->email, $display);
        $this->assertTableContains($user1->name, $display);
        $this->assertContains(trans('command/messages.user.deleted'), $display);
    }

    /**
     * Test the ability to re-do a search for a user account.
     */
    public function testReSearchAbility()
    {
        $users = collect([
            $user1 = factory(User::class)->make(),
        ]);

        $this->repository->shouldReceive('search')->with($user1->username)->twice()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->twice()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1->id)->once()->andReturnNull();

        $response = new CommandTester($this->command);
        $response->setInputs([$user1->username, 0, $user1->username, $user1->id, 'yes']);
        $response->execute([]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.select_search_user'), $display);
        $this->assertTableContains($user1->id, $display);
        $this->assertTableContains($user1->email, $display);
        $this->assertTableContains($user1->name, $display);
        $this->assertContains(trans('command/messages.user.deleted'), $display);
    }

    /**
     * Test that answering no works as expected when confirming deletion of account.
     */
    public function testAnsweringNoToDeletionConfirmationWillNotDeleteUser()
    {
        $users = collect([
            $user1 = factory(User::class)->make(),
        ]);

        $this->repository->shouldReceive('search')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldNotReceive('handle');

        $response = new CommandTester($this->command);
        $response->setInputs([$user1->username, $user1->id, 'no']);
        $response->execute([]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertNotContains(trans('command/messages.user.deleted'), $display);
    }

    /**
     * Test a single result is deleted if there is no interaction setup.
     */
    public function testNoInteractionWithSingleResult()
    {
        $users = collect([
            $user1 = factory(User::class)->make(),
        ]);

        $this->repository->shouldReceive('search')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1)->once()->andReturnNull();

        $response = new CommandTester($this->command);
        $response->execute(['--user' => $user1->username], ['interactive' => false]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.deleted'), $display);
    }

    /**
     * Test that an error is returned if there is no interaction but multiple results.
     */
    public function testNoInteractionWithMultipleResults()
    {
        $users = collect([
            $user1 = factory(User::class)->make(),
            $user2 = factory(User::class)->make(),
        ]);

        $this->repository->shouldReceive('search')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldNotReceive('handle');

        $response = new CommandTester($this->command);
        $response->execute(['--user' => $user1->username], ['interactive' => false]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.multiple_found'), $display);
    }

    /**
     * Test that an error is returned if there is no interaction and no results returned.
     */
    public function testNoInteractionWithNoResults()
    {
        $this->repository->shouldReceive('search')->with(123456)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn([]);

        $response = new CommandTester($this->command);
        $response->execute(['--user' => 123456], ['interactive' => false]);

        $display = $response->getDisplay();
        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.no_users_found'), $display);
    }
}
