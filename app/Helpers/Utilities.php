<?php

declare(strict_types=1);

namespace Pterodactyl\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Utilities
{
    /**
     * Generates a random string and injects special characters into it, in addition to
     * the randomness of the alphanumeric default response.
     */
    public static function randomStringWithSpecialCharacters(int $length = 16): string
    {
        $string = Str::random($length);
        // Given a random string of characters, randomly loop through the characters and replace some
        // with special characters to avoid issues with MySQL password requirements on some servers.
        try {
            for ($i = 0; $i < random_int(2, 6); $i++) {
                $character = ['!', '@', '=', '.', '+', '^'][random_int(0, 5)];

                $string = substr_replace($string, $character, random_int(0, $length - 1), 1);
            }
        } catch (Exception $exception) {
            // Just log the error and hope for the best at this point.
            Log::error($exception->getMessage(), ['exception' => $exception]);
        }

        return $string;
    }
}
