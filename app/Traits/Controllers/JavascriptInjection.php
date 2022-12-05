<?php

namespace Pterodactyl\Traits\Controllers;

use Illuminate\Http\Request;

trait JavascriptInjection
{
    private Request $request;

    /**
     * Set the request object to use when injecting JS.
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Injects the exact array passed in, nothing more.
     */
    public function plainInject(array $args = []): string
    {
        return \JavaScript::put($args);
    }
}
