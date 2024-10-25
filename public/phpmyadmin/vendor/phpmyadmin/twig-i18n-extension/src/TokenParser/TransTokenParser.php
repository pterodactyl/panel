<?php

declare(strict_types=1);

/*
 * This file is part of Twig I18n extension.
 *
 * (c) 2010-2019 Fabien Potencier
 * (c) 2019-2021 phpMyAdmin contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpMyAdmin\Twig\Extensions\TokenParser;

use PhpMyAdmin\Twig\Extensions\Node\TransNode;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class TransTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        [
            $body,
            $plural,
            $count,
            $context,
            $notes,
            $domain,
            $lineno,
            $tag,
        ] = $this->preParse($token);

        return new TransNode($body, $plural, $count, $context, $notes, $domain, $lineno, $tag);
    }

    protected function preParse(Token $token): array
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $domain = null;
        $count = null;
        $plural = null;
        $notes = null;
        $context = null;

        /* If we aren't closing the block, do we have a domain? */
        if ($stream->test(Token::NAME_TYPE)) {
            $stream->expect(Token::NAME_TYPE, 'from');
            $domain = $this->parser->getExpressionParser()->parseExpression();
        }

        if (! $stream->test(Token::BLOCK_END_TYPE)) {
            $body = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $stream->expect(Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse([$this, 'decideForFork']);
            $next = $stream->next()->getValue();

            if ($next === 'plural') {
                $count = $this->parser->getExpressionParser()->parseExpression();
                $stream->expect(Token::BLOCK_END_TYPE);
                $plural = $this->parser->subparse([$this, 'decideForFork']);
                $next = $stream->next()->getValue();
                if ($next === 'notes') {
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $notes = $this->parser->subparse([$this, 'decideForEnd'], true);
                } elseif ($next === 'context') {
                    $stream->expect(Token::BLOCK_END_TYPE);
                    $context = $this->parser->subparse([$this, 'decideForEnd'], true);
                }
            } elseif ($next === 'context') {
                $stream->expect(Token::BLOCK_END_TYPE);
                $context = $this->parser->subparse([$this, 'decideForEnd'], true);
            } elseif ($next === 'notes') {
                $stream->expect(Token::BLOCK_END_TYPE);
                $notes = $this->parser->subparse([$this, 'decideForEnd'], true);
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        $this->checkTransString($body, $lineno);

        return [$body, $plural, $count, $context, $notes, $domain, $lineno, $this->getTag()];
    }

    /**
     * @return bool
     */
    public function decideForFork(Token $token)
    {
        return $token->test(['plural', 'context', 'notes', 'endtrans']);
    }

    /**
     * @return bool
     */
    public function decideForEnd(Token $token)
    {
        return $token->test('endtrans');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'trans';
    }

    /**
     * @return void
     *
     * @throws SyntaxError
     */
    protected function checkTransString(Node $body, int $lineno)
    {
        foreach ($body as $i => $node) {
            if (
                $node instanceof TextNode
                ||
                ($node instanceof PrintNode && $node->getNode('expr') instanceof NameExpression)
            ) {
                continue;
            }

            throw new SyntaxError(
                'The text to be translated with "trans" can only contain references to simple variables.',
                $lineno
            );
        }
    }
}
