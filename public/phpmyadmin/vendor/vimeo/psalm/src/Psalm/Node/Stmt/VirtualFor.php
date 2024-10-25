<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\For_;
use Psalm\Node\VirtualNode;

class VirtualFor extends For_ implements VirtualNode
{

}
