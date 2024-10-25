<?php

declare(strict_types=1);

namespace Psalm\Node\Expr\BinaryOp;

use PhpParser\Node\Expr\BinaryOp\Coalesce;
use Psalm\Node\VirtualNode;

class VirtualCoalesce extends Coalesce implements VirtualNode
{

}
