<?php

declare(strict_types=1);

/*
 * This file is part of Twig I18n extension.
 *
 * (c) 2025 phpMyAdmin contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMyAdmin\Twig\Extensions\Node;

use Twig\Attribute\YieldReady;
use Twig\Node\Node;

/**
 * Represents a single node
 */
#[YieldReady]
final class I18nNode extends Node
{
    /**
     * @param Node|null            $node       A single node
     * @param array<string, mixed> $attributes An array of attributes (should not be nodes)
     * @param int                  $lineno     The line number
     */
    public function __construct(?Node $node, array $attributes, int $lineno)
    {
        parent::__construct($node === null ? [] : [$node], $attributes, $lineno);
    }
}
