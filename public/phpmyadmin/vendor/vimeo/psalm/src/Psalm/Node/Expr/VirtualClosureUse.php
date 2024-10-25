<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ClosureUse;
use Psalm\Node\VirtualNode;

class VirtualClosureUse extends ClosureUse implements VirtualNode
{

}
