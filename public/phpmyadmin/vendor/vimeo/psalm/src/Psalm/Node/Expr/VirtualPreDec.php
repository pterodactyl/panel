<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\PreDec;
use Psalm\Node\VirtualNode;

class VirtualPreDec extends PreDec implements VirtualNode
{

}
