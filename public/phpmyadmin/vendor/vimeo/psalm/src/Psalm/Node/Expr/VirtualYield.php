<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Yield_;
use Psalm\Node\VirtualNode;

class VirtualYield extends Yield_ implements VirtualNode
{

}
