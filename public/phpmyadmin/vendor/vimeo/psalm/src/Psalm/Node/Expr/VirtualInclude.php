<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Include_;
use Psalm\Node\VirtualNode;

class VirtualInclude extends Include_ implements VirtualNode
{

}
