<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Service;

use Pterodactyl\Exceptions\DisplayException;

/**
 * Exception thrown when something goes over a defined limit, such as allocated
 * ports, tasks, databases, etc.
 */
class ServiceLimitExceededException extends DisplayException {}
