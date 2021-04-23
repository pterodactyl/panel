<?php

namespace Pterodactyl\Tests\Browser;

use Laravel\Dusk\Browser;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

class PterodactylBrowser extends Browser
{
    /**
     * Move the mouse to a specific location and then perform a left click action.
     *
     * @return $this
     */
    public function clickPosition(int $x, int $y)
    {
        $this->driver->getMouse()->mouseMove(null, $x, $y)->click();

        return $this;
    }

    /**
     * Perform a case insensitive search for a string in the body.
     *
     * @param string $text
     *
     * @return \Pterodactyl\Tests\Browser\PterodactylBrowser
     */
    public function assertSee($text)
    {
        return $this->assertSeeIn('', $text);
    }

    /**
     * Perform a case insensitive search for a string in a given selector.
     *
     * @param string $selector
     * @param string $text
     *
     * @return \Pterodactyl\Tests\Browser\PterodactylBrowser
     */
    public function assertSeeIn($selector, $text)
    {
        $fullSelector = $this->resolver->format($selector);
        $element = $this->resolver->findOrFail($selector);

        PHPUnit::assertTrue(
            Str::contains(mb_strtolower($element->getText()), mb_strtolower($text)),
            "Did not see expected text [{$text}] within element [{$fullSelector}] using case-insensitive search."
        );

        return $this;
    }
}
