<?php

namespace Pterodactyl\Exceptions;

use Exception;
use Spatie\Ignition\Contracts\Solution;
use Spatie\Ignition\Contracts\ProvidesSolution;

class ManifestDoesNotExistException extends Exception implements ProvidesSolution
{
    public function getSolution(): Solution
    {
        return new Solutions\ManifestDoesNotExistSolution();
    }
}
