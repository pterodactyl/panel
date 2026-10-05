<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Model as IlluminateModel;

#[RouteKey('uuid')]
abstract class Model extends IlluminateModel {}
