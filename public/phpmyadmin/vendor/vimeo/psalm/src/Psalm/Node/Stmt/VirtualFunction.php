<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Function_;
use Psalm\Node\VirtualNode;

class VirtualFunction extends Function_ implements VirtualNode
{

}
