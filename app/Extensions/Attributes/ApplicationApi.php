<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions\Attributes;

use Attribute;

/**
 * Marks extension Fields the Application API accepts and returns as well, under the same
 * `extensions.<id>` key, when creating and updating users, nodes, locations and servers.
 * Billing systems that provision through the Application API can then set them.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ApplicationApi {}
