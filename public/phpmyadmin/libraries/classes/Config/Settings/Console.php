<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function in_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class Console
{
    /** @var bool */
    public $StartHistory;

    /** @var bool */
    public $AlwaysExpand;

    /** @var bool */
    public $CurrentQuery;

    /** @var bool */
    public $EnterExecutes;

    /** @var bool */
    public $DarkTheme;

    /**
     * @var string
     * @psalm-var 'info'|'show'|'collapse'
     */
    public $Mode;

    /**
     * @var int
     * @psalm-var positive-int
     */
    public $Height;

    /** @var bool */
    public $GroupQueries;

    /**
     * @var string
     * @psalm-var 'exec'|'time'|'count'
     */
    public $OrderBy;

    /**
     * @var string
     * @psalm-var 'asc'|'desc'
     */
    public $Order;

    /**
     * @param mixed[] $console
     */
    public function __construct(array $console = [])
    {
        $this->StartHistory = $this->setStartHistory($console);
        $this->AlwaysExpand = $this->setAlwaysExpand($console);
        $this->CurrentQuery = $this->setCurrentQuery($console);
        $this->EnterExecutes = $this->setEnterExecutes($console);
        $this->DarkTheme = $this->setDarkTheme($console);
        $this->Mode = $this->setMode($console);
        $this->Height = $this->setHeight($console);
        $this->GroupQueries = $this->setGroupQueries($console);
        $this->OrderBy = $this->setOrderBy($console);
        $this->Order = $this->setOrder($console);
    }

    /**
     * @param mixed[] $console
     */
    private function setStartHistory(array $console): bool
    {
        if (isset($console['StartHistory'])) {
            return (bool) $console['StartHistory'];
        }

        return false;
    }

    /**
     * @param mixed[] $console
     */
    private function setAlwaysExpand(array $console): bool
    {
        if (isset($console['AlwaysExpand'])) {
            return (bool) $console['AlwaysExpand'];
        }

        return false;
    }

    /**
     * @param mixed[] $console
     */
    private function setCurrentQuery(array $console): bool
    {
        if (isset($console['CurrentQuery'])) {
            return (bool) $console['CurrentQuery'];
        }

        return true;
    }

    /**
     * @param mixed[] $console
     */
    private function setEnterExecutes(array $console): bool
    {
        if (isset($console['EnterExecutes'])) {
            return (bool) $console['EnterExecutes'];
        }

        return false;
    }

    /**
     * @param mixed[] $console
     */
    private function setDarkTheme(array $console): bool
    {
        if (isset($console['DarkTheme'])) {
            return (bool) $console['DarkTheme'];
        }

        return false;
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return 'info'|'show'|'collapse'
     */
    private function setMode(array $console): string
    {
        if (isset($console['Mode']) && in_array($console['Mode'], ['show', 'collapse'], true)) {
            return $console['Mode'];
        }

        return 'info';
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return positive-int
     */
    private function setHeight(array $console): int
    {
        if (isset($console['Height'])) {
            $height = (int) $console['Height'];
            if ($height >= 1) {
                return $height;
            }
        }

        return 92;
    }

    /**
     * @param mixed[] $console
     */
    private function setGroupQueries(array $console): bool
    {
        if (isset($console['GroupQueries'])) {
            return (bool) $console['GroupQueries'];
        }

        return false;
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return 'exec'|'time'|'count'
     */
    private function setOrderBy(array $console): string
    {
        if (isset($console['OrderBy']) && in_array($console['OrderBy'], ['time', 'count'], true)) {
            return $console['OrderBy'];
        }

        return 'exec';
    }

    /**
     * @param mixed[] $console
     *
     * @psalm-return 'asc'|'desc'
     */
    private function setOrder(array $console): string
    {
        if (isset($console['Order']) && $console['Order'] === 'desc') {
            return 'desc';
        }

        return 'asc';
    }
}
