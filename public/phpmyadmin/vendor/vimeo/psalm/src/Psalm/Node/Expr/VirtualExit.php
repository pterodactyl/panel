<?php

declare(strict_types=1);

namespace Psalm\Node\Expr;

use PhpParser\Node\Expr\Exit_;
use Psalm\Node\VirtualNode;

class VirtualExit extends Exit_ implements VirtualNode
{

}
