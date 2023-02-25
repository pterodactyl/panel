<?php

namespace Tests\Unit;

use App\Classes\Pterodactyl;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TestUserCommand extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     *
     * @dataProvider invalidPteroIdDataProvider
     *
     * @param  array  $apiResponse
     * @param  int  $expectedExitCode
     * @return void
     */
    public function testMakeUserCommand(array $apiResponse, int $expectedExitCode): void
    {
        $pterodactyl = $this->getMockBuilder(Pterodactyl::class)->getMock();
        $pterodactyl->expects(self::once())->method('getUser')->willReturn($apiResponse);

        $this->app->instance(Pterodactyl::class, $pterodactyl);

        $this->artisan('make:user')
            ->expectsQuestion('Please specify your Pterodactyl ID.', 0)
            ->expectsQuestion('Please specify your password.', 'password')
            ->assertExitCode($expectedExitCode);
    }

    public function invalidPteroIdDataProvider(): array
    {
        return [
            'Good Response' => [
                'apiResponse' => [
                    'id' => 12345,
                    'first_name' => 'Test',
                    'email' => 'test@test.test',
                ],
                'expectedExitCode' => 1,
            ],
            'Bad Response' => [
                'apiResponse' => [],
                'expectedExitCode' => 0,
            ],
        ];
    }
}
