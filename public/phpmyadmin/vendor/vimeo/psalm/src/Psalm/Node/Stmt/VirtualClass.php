<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Class_;
use Psalm\Node\VirtualNode;

class VirtualClass extends Class_ implements VirtualNode
{

}
