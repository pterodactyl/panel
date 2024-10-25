<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\AssignOp;

use PhpParser\Node\Expr\AssignOp\Mod;
use Psalm\Node\VirtualNode;

class VirtualMod extends Mod implements VirtualNode
{

}
