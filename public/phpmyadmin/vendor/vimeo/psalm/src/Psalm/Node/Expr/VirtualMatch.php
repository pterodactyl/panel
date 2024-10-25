<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Match_;
use Psalm\Node\VirtualNode;

class VirtualMatch extends Match_ implements VirtualNode
{

}
