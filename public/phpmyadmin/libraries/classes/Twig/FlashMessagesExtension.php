<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\FlashMessages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FlashMessagesExtension extends AbstractExtension
{
    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [new TwigFunction('flash', [FlashMessages::class, 'getMessages'])];
    }
}
