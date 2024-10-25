<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\ShellExec;
use Psalm\Node\VirtualNode;

class VirtualShellExec extends ShellExec implements VirtualNode
{

}
