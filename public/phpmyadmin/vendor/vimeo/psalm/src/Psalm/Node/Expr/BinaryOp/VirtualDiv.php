<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Div;
use Psalm\Node\VirtualNode;

class VirtualDiv extends Div implements VirtualNode
{

}
