<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\ClassConst;
use Psalm\Node\VirtualNode;

class VirtualClassConst extends ClassConst implements VirtualNode
{

}
