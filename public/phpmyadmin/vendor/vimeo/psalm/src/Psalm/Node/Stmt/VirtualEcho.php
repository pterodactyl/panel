<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Echo_;
use Psalm\Node\VirtualNode;

class VirtualEcho extends Echo_ implements VirtualNode
{

}
