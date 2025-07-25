<?php

namespace Pterodactyl\Tests\Integration;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert as PHPUnit;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Validation\ValidationException;
use Illuminate\Testing\TestResponse as IlluminateTestResponse;

class TestResponse extends IlluminateTestResponse
{
    /**
     * Overrides the default assert status logic to dump out the error to the
     * test output if it is caused by a 500 level error, and we were not specifically
     * look for that status response.
     */
    public function assertStatus($status): TestResponse
    {
        $actual = $this->getStatusCode();

        // Dump the response to the screen before making the assertion which is going
        // to fail so that debugging isn't such a nightmare.
        if ($actual !== $status && $status !== 500) {
            $this->dump();
            if (!is_null($this->exception) && !$this->exception instanceof DisplayException && !$this->exception instanceof ValidationException) {
                dump([
                    'exception_class' => get_class($this->exception),
                    'message' => $this->exception->getMessage(),
                    'trace' => $this->exception->getTrace(),
                ]);
            }
        }

        PHPUnit::assertSame($actual, $status, "Expected status code {$status} but received {$actual}.");

        return $this;
    }

    public function assertForbidden(): self
    {
        return self::assertStatus(Response::HTTP_FORBIDDEN);
    }
}
