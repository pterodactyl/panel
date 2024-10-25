<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parser;
use RuntimeException;

/**
 * `WITH` keyword builder.
 *
 * @final
 */
final class WithKeyword extends Component
{
    /** @var string */
    public $name;

    /** @var ArrayObj[] */
    public $columns = [];

    /** @var Parser|null */
    public $statement;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param WithKeyword          $component
     * @param array<string, mixed> $options
     *
     * @return string
     */
    public static function build($component, array $options = [])
    {
        if (! $component instanceof WithKeyword) {
            throw new RuntimeException('Can not build a component that is not a WithKeyword');
        }

        if (! isset($component->statement)) {
            throw new RuntimeException('No statement inside WITH');
        }

        $str = $component->name;

        if ($component->columns) {
            $str .= ArrayObj::build($component->columns);
        }

        $str .= ' AS (';

        foreach ($component->statement->statements as $statement) {
            $str .= $statement->build();
        }

        $str .= ')';

        return $str;
    }
}
