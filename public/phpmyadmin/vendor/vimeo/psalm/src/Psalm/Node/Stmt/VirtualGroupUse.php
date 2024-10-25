<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\GroupUse;
use Psalm\Node\VirtualNode;

class VirtualGroupUse extends GroupUse implements VirtualNode
{

}
