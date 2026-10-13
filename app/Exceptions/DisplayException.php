<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * A 400 whose message is shown to the caller. API routes render it as JSON even when
 * the request does not ask for JSON (see bootstrap/app.php).
 */
class DisplayException extends BadRequestHttpException {}
