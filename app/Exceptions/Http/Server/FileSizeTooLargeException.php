<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Http\Server;

use Pterodactyl\Exceptions\DisplayException;

class FileSizeTooLargeException extends DisplayException
{
    /**
     * FileSizeTooLargeException constructor.
     */
    public function __construct()
    {
        parent::__construct(__('exceptions.files.too_large'));
    }
}
