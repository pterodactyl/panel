<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use ArrayObject;
use PhpMyAdmin\Query\Utilities;

use function in_array;

/**
 * @extends ArrayObject<int, string>
 */
abstract class ListAbstract extends ArrayObject
{
    /** @var mixed   empty item */
    protected $itemEmpty = '';

    /**
     * defines what is an empty item (0, '', false or null)
     *
     * @return mixed   an empty item
     */
    public function getEmpty()
    {
        return $this->itemEmpty;
    }

    /**
     * checks if the given db names exists in the current list, if there is
     * missing at least one item it returns false otherwise true
     *
     * @param mixed[] ...$params params
     */
    public function exists(...$params): bool
    {
        $this_elements = $this->getArrayCopy();
        foreach ($params as $result) {
            if (! in_array($result, $this_elements)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array<string, bool|string>>
     */
    public function getList(): array
    {
        $selected = $this->getDefault();

        $list = [];
        foreach ($this as $eachItem) {
            if (Utilities::isSystemSchema($eachItem)) {
                continue;
            }

            $list[] = [
                'name' => $eachItem,
                'is_selected' => $selected === $eachItem,
            ];
        }

        return $list;
    }

    /**
     * returns default item
     *
     * @return string  default item
     */
    public function getDefault()
    {
        return $this->getEmpty();
    }

    /**
     * builds up the list
     */
    abstract public function build(): void;
}
