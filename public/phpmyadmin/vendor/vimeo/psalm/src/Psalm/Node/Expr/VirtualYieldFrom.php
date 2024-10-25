<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\YieldFrom;
use Psalm\Node\VirtualNode;

class VirtualYieldFrom extends YieldFrom implements VirtualNode
{

}
