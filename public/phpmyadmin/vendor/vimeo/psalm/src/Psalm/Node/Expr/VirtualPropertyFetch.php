<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\PropertyFetch;
use Psalm\Node\VirtualNode;

class VirtualPropertyFetch extends PropertyFetch implements VirtualNode
{

}
