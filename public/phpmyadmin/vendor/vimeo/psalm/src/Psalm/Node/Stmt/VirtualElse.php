<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Else_;
use Psalm\Node\VirtualNode;

class VirtualElse extends Else_ implements VirtualNode
{

}
