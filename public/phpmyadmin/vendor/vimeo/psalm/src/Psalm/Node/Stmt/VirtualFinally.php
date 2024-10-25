<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Finally_;
use Psalm\Node\VirtualNode;

class VirtualFinally extends Finally_ implements VirtualNode
{

}
