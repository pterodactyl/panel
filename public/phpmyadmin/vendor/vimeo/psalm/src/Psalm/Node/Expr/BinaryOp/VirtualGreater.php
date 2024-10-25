<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Greater;
use Psalm\Node\VirtualNode;

class VirtualGreater extends Greater implements VirtualNode
{

}
