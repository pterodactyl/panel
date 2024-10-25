<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\ListDatabase;

class DatabaseList
{
    /**
     * Holds database list
     *
     * @var ListDatabase
     */
    protected $databases = null;

    /**
     * magic access to protected/inaccessible members/properties
     *
     * @see https://www.php.net/language.oop5.overloading
     *
     * @param string $param parameter name
     *
     * @return mixed
     */
    public function __get($param)
    {
        switch ($param) {
            case 'databases':
                return $this->getDatabaseList();
        }

        return null;
    }

    /**
     * Accessor to PMA::$databases
     */
    public function getDatabaseList(): ListDatabase
    {
        if ($this->databases === null) {
            $this->databases = new ListDatabase();
        }

        return $this->databases;
    }
}
