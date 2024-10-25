<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\NullsafePropertyFetch;
use Psalm\Node\VirtualNode;

class VirtualNullsafePropertyFetch extends NullsafePropertyFetch implements VirtualNode
{

}
