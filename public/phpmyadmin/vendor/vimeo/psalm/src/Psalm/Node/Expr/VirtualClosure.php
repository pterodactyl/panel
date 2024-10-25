<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Closure;
use Psalm\Node\VirtualNode;

class VirtualClosure extends Closure implements VirtualNode
{

}
