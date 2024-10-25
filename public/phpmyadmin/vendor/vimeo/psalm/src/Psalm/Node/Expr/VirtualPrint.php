<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Print_;
use Psalm\Node\VirtualNode;

class VirtualPrint extends Print_ implements VirtualNode
{

}
