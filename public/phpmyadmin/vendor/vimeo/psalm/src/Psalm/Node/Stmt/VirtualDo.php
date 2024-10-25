<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Do_;
use Psalm\Node\VirtualNode;

class VirtualDo extends Do_ implements VirtualNode
{

}
