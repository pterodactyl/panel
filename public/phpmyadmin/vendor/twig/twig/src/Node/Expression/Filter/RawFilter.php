<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression\Filter;

use Twig\Compiler;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Node;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RawFilter extends FilterExpression
{
    public function __construct(Node $node, ?ConstantExpression $filterName = null, ?Node $arguments = null, int $lineno = 0, ?string $tag = null)
    {
        if (null === $filterName) {
            $filterName = new ConstantExpression('raw', $node->getTemplateLine());
        }
        if (null === $arguments) {
            $arguments = new Node();
        }

        parent::__construct($node, $filterName, $arguments, $lineno ?: $node->getTemplateLine(), $tag ?: $node->getNodeTag());
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
