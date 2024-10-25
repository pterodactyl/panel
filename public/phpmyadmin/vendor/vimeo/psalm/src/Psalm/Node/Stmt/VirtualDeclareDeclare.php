<?php

declare(strict_types=1);

namespace Psalm\Node\Stmt;

use PhpParser\Node\Stmt\DeclareDeclare;
use Psalm\Node\VirtualNode;

class VirtualDeclareDeclare extends DeclareDeclare implements VirtualNode
{

}
