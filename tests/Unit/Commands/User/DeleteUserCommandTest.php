<?php

namespace Tests\Unit\Commands\User;

use Mockery as m;
use Pterodactyl\Models\User;
use Tests\Unit\Commands\CommandTestCase;
use Tests\Assertions\CommandAssertionsTrait;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Console\Commands\User\DeleteUserCommand;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class DeleteUserCommandTest extends CommandTestCase
{
    use CommandAssertionsTrait;

    /**
     * @var \Pterodactyl\Console\Commands\User\DeleteUserCommand
     */
    protected $command;

    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService|\Mockery\Mock
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
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

        $this->repository->shouldReceive('setSearchTerm')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1->id)->once()->andReturnNull();

        $display = $this->runCommand($this->command, [], [$user1->username, $user1->id, 'yes']);

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

        $this->repository->shouldReceive('setSearchTerm')->with('noResults')->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn(collect());
        $this->repository->shouldReceive('setSearchTerm')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1->id)->once()->andReturnNull();

        $display = $this->runCommand($this->command, [], ['noResults', $user1->username, $user1->id, 'yes']);

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

        $this->repository->shouldReceive('setSearchTerm')->with($user1->username)->twice()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->twice()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1->id)->once()->andReturnNull();

        $display = $this->runCommand($this->command, [], [$user1->username, 0, $user1->username, $user1->id, 'yes']);

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

        $this->repository->shouldReceive('setSearchTerm')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldNotReceive('handle');

        $display = $this->runCommand($this->command, [], [$user1->username, $user1->id, 'no']);

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

        $this->repository->shouldReceive('setSearchTerm')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldReceive('handle')->with($user1)->once()->andReturnNull();

        $display = $this->withoutInteraction()->runCommand($this->command, ['--user' => $user1->username]);

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

        $this->repository->shouldReceive('setSearchTerm')->with($user1->username)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn($users);
        $this->deletionService->shouldNotReceive('handle');

        $display = $this->withoutInteraction()->runCommand($this->command, ['--user' => $user1->username]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.multiple_found'), $display);
    }

    /**
     * Test that an error is returned if there is no interaction and no results returned.
     */
    public function testNoInteractionWithNoResults()
    {
        $this->repository->shouldReceive('setSearchTerm')->with(123456)->once()->andReturnSelf()
            ->shouldReceive('all')->withNoArgs()->once()->andReturn(collect());

        $display = $this->withoutInteraction()->runCommand($this->command, ['--user' => 123456]);

        $this->assertNotEmpty($display);
        $this->assertContains(trans('command/messages.user.no_users_found'), $display);
    }
}
