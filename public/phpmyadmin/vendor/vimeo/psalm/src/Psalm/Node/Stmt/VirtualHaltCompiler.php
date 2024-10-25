<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\HaltCompiler;
use Psalm\Node\VirtualNode;

class VirtualHaltCompiler extends HaltCompiler implements VirtualNode
{

}
