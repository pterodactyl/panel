<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\ElseIf_;
use Psalm\Node\VirtualNode;

class VirtualElseIf extends ElseIf_ implements VirtualNode
{

}
