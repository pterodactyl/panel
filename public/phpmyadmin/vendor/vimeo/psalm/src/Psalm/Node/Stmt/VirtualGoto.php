<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Goto_;
use Psalm\Node\VirtualNode;

class VirtualGoto extends Goto_ implements VirtualNode
{

}
