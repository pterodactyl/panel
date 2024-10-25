<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\UseUse;
use Psalm\Node\VirtualNode;

class VirtualUseUse extends UseUse implements VirtualNode
{

}
