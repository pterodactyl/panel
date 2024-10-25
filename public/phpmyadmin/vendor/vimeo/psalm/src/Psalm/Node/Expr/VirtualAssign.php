<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Assign;
use Psalm\Node\VirtualNode;

class VirtualAssign extends Assign implements VirtualNode
{

}
