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
use Pterodactyl\Models\User;
use Pterodactyl\Services\UserService;
use Illuminate\Support\Facades\Notification;
use Pterodactyl\Notifications\AccountCreated;

class UserServiceTest extends TestCase
{
    protected $service;

    public function setUp()
    {
        parent::setUp();

        $this->service = $this->app->make(UserService::class);
    }

    public function testShouldReturnNewUserWithValidData()
    {
        Notification::fake();

        $user = $this->service->create([
            'email' => 'test_account@example.com',
            'username' => 'test_account',
            'password' => 'test_password',
            'name_first' => 'Test',
            'name_last' => 'Account',
            'root_admin' => false,
        ]);

        $this->assertNotNull($user->uuid);
        $this->assertNotEquals($user->password, 'test_password');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'email' => 'test_account@example.com',
            'root_admin' => '0',
        ]);

        Notification::assertSentTo($user, AccountCreated::class, function ($notification) use ($user) {
            $this->assertEquals($user->username, $notification->user->username);
            $this->assertNull($notification->user->token);

            return true;
        });
    }

    public function testShouldReturnNewUserWithPasswordTokenIfNoPasswordProvided()
    {
        Notification::fake();

        $user = $this->service->create([
            'email' => 'test_account@example.com',
            'username' => 'test_account',
            'name_first' => 'Test',
            'name_last' => 'Account',
            'root_admin' => false,
        ]);

        $this->assertNotNull($user->uuid);
        $this->assertNotNull($user->password);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'email' => 'test_account@example.com',
            'root_admin' => '0',
        ]);

        Notification::assertSentTo($user, AccountCreated::class, function ($notification) use ($user) {
            $this->assertEquals($user->username, $notification->user->username);
            $this->assertNotNull($notification->user->token);

            $this->assertDatabaseHas('password_resets', [
                'email' => $user->email,
            ]);

            return true;
        });
    }

    public function testShouldUpdateUserModelInDatabase()
    {
        //        $user = factory(User::class)->create();
//
//        $response = $this->service->update($user, [
//            'email' => 'test_change@example.com',
//            'password' => 'test_password',
//        ]);
//
//        $this->assertInstanceOf(User::class, $response);
//        $this->assertEquals('test_change@example.com', $response->email);
//        $this->assertNotEquals($response->password, 'test_password');
//        $this->assertDatabaseHas('users', [
//            'id' => $user->id,
//            'email' => 'test_change@example.com',
//        ]);
    }

    public function testShouldDeleteUserFromDatabase()
    {
        //        $user = factory(User::class)->create();
//        $service = $this->app->make(UserService::class);
//
//        $response = $service->delete($user);
//
//        $this->assertTrue($response);
//        $this->assertDatabaseMissing('users', [
//            'id' => $user->id,
//            'uuid' => $user->uuid,
//        ]);
    }
}
