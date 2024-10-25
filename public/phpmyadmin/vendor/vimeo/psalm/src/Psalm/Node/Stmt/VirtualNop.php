<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\Nop;
use Psalm\Node\VirtualNode;

/** Nop/empty statement (;). */
class VirtualNop extends Nop implements VirtualNode
{

}
