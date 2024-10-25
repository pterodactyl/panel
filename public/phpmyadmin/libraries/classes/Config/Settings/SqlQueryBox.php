<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class SqlQueryBox
{
    /**
     * Display an "Edit" link on the results page to change a query.
     *
     * @var bool
     */
    public $Edit;

    /**
     * Display an "Explain SQL" link on the results page.
     *
     * @var bool
     */
    public $Explain;

    /**
     * Display a "Create PHP code" link on the results page to wrap a query in PHP.
     *
     * @var bool
     */
    public $ShowAsPHP;

    /**
     * Display a "Refresh" link on the results page.
     *
     * @var bool
     */
    public $Refresh;

    /**
     * @param mixed[] $sqlQueryBox
     */
    public function __construct(array $sqlQueryBox = [])
    {
        $this->Edit = $this->setEdit($sqlQueryBox);
        $this->Explain = $this->setExplain($sqlQueryBox);
        $this->ShowAsPHP = $this->setShowAsPHP($sqlQueryBox);
        $this->Refresh = $this->setRefresh($sqlQueryBox);
    }

    /**
     * @param mixed[] $sqlQueryBox
     */
    private function setEdit(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['Edit']) || $sqlQueryBox['Edit'];
    }

    /**
     * @param mixed[] $sqlQueryBox
     */
    private function setExplain(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['Explain']) || $sqlQueryBox['Explain'];
    }

    /**
     * @param mixed[] $sqlQueryBox
     */
    private function setShowAsPHP(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['ShowAsPHP']) || $sqlQueryBox['ShowAsPHP'];
    }

    /**
     * @param mixed[] $sqlQueryBox
     */
    private function setRefresh(array $sqlQueryBox): bool
    {
        return ! isset($sqlQueryBox['Refresh']) || $sqlQueryBox['Refresh'];
    }
}
