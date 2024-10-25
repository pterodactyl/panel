<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;
use function array_pop;
use function count;
use function htmlspecialchars;
use function strlen;

/**
 * Index manipulation class
 */
class Index
{
    public const PRIMARY = 1;
    public const UNIQUE = 2;
    public const INDEX = 4;
    public const SPATIAL = 8;
    public const FULLTEXT = 16;

    /**
     * Class-wide storage container for indexes (caching, singleton)
     *
     * @var array
     */
    private static $registry = [];

    /** @var string The name of the schema */
    private $schema = '';

    /** @var string The name of the table */
    private $table = '';

    /** @var string The name of the index */
    private $name = '';

    /**
     * Columns in index
     *
     * @var array
     */
    private $columns = [];

    /**
     * The index method used (BTREE, HASH, RTREE).
     *
     * @var string
     */
    private $type = '';

    /**
     * The index choice (PRIMARY, UNIQUE, INDEX, SPATIAL, FULLTEXT)
     *
     * @var string
     */
    private $choice = '';

    /**
     * Various remarks.
     *
     * @var string
     */
    private $remarks = '';

    /**
     * Any comment provided for the index with a COMMENT attribute when the
     * index was created.
     *
     * @var string
     */
    private $comment = '';

    /** @var int 0 if the index cannot contain duplicates, 1 if it can. */
    private $nonUnique = 0;

    /**
     * Indicates how the key is packed. NULL if it is not.
     *
     * @var string
     */
    private $packed = null;

    /**
     * Block size for the index
     *
     * @var int
     */
    private $keyBlockSize = null;

    /**
     * Parser option for the index
     *
     * @var string
     */
    private $parser = null;

    /**
     * @param array $params parameters
     */
    public function __construct(array $params = [])
    {
        $this->set($params);
    }

    /**
     * Creates(if not already created) and returns the corresponding Index object
     *
     * @param string $schema     database name
     * @param string $table      table name
     * @param string $index_name index name
     *
     * @return Index corresponding Index object
     */
    public static function singleton($schema, $table, $index_name = '')
    {
        self::loadIndexes($table, $schema);
        if (! isset(self::$registry[$schema][$table][$index_name])) {
            $index = new Index();
            if (strlen($index_name) > 0) {
                $index->setName($index_name);
                self::$registry[$schema][$table][$index->getName()] = $index;
            }

            return $index;
        }

        return self::$registry[$schema][$table][$index_name];
    }

    /**
     * returns an array with all indexes from the given table
     *
     * @param string $table  table
     * @param string $schema schema
     *
     * @return Index[]  array of indexes
     */
    public static function getFromTable($table, $schema)
    {
        self::loadIndexes($table, $schema);

        if (isset(self::$registry[$schema][$table])) {
            return self::$registry[$schema][$table];
        }

        return [];
    }

    /**
     * Returns an array with all indexes from the given table of the requested types
     *
     * @param string $table   table
     * @param string $schema  schema
     * @param int    $choices choices
     *
     * @return Index[] array of indexes
     */
    public static function getFromTableByChoice($table, $schema, $choices = 31)
    {
        $indexes = [];
        foreach (self::getFromTable($table, $schema) as $index) {
            if (($choices & self::PRIMARY) && $index->getChoice() === 'PRIMARY') {
                $indexes[] = $index;
            }

            if (($choices & self::UNIQUE) && $index->getChoice() === 'UNIQUE') {
                $indexes[] = $index;
            }

            if (($choices & self::INDEX) && $index->getChoice() === 'INDEX') {
                $indexes[] = $index;
            }

            if (($choices & self::SPATIAL) && $index->getChoice() === 'SPATIAL') {
                $indexes[] = $index;
            }

            if ((! ($choices & self::FULLTEXT)) || $index->getChoice() !== 'FULLTEXT') {
                continue;
            }

            $indexes[] = $index;
        }

        return $indexes;
    }

    /**
     * return primary if set, false otherwise
     *
     * @param string $table  table
     * @param string $schema schema
     *
     * @return Index|false primary index or false if no one exists
     */
    public static function getPrimary($table, $schema)
    {
        self::loadIndexes($table, $schema);

        if (isset(self::$registry[$schema][$table]['PRIMARY'])) {
            return self::$registry[$schema][$table]['PRIMARY'];
        }

        return false;
    }

    /**
     * Load index data for table
     *
     * @param string $table  table
     * @param string $schema schema
     */
    private static function loadIndexes($table, $schema): bool
    {
        global $dbi;

        if (isset(self::$registry[$schema][$table])) {
            return true;
        }

        $_raw_indexes = $dbi->getTableIndexes($schema, $table);
        foreach ($_raw_indexes as $_each_index) {
            $_each_index['Schema'] = $schema;
            $keyName = $_each_index['Key_name'];
            if (! isset(self::$registry[$schema][$table][$keyName])) {
                $key = new Index($_each_index);
                self::$registry[$schema][$table][$keyName] = $key;
            } else {
                $key = self::$registry[$schema][$table][$keyName];
            }

            $key->addColumn($_each_index);
        }

        return true;
    }

    /**
     * Add column to index
     *
     * @param array $params column params
     */
    public function addColumn(array $params): void
    {
        $key = $params['Column_name'] ?? $params['Expression'] ?? '';
        if (isset($params['Expression'])) {
            // The Expression only does not make the key unique, add a sequence number
            $key .= $params['Seq_in_index'];
        }

        if (strlen($key) <= 0) {
            return;
        }

        $this->columns[$key] = new IndexColumn($params);
    }

    /**
     * Adds a list of columns to the index
     *
     * @param array $columns array containing details about the columns
     */
    public function addColumns(array $columns): void
    {
        $_columns = [];

        if (isset($columns['names'])) {
            // coming from form
            // $columns[names][]
            // $columns[sub_parts][]
            foreach ($columns['names'] as $key => $name) {
                $sub_part = $columns['sub_parts'][$key] ?? '';
                $_columns[] = [
                    'Column_name' => $name,
                    'Sub_part' => $sub_part,
                ];
            }
        } else {
            // coming from SHOW INDEXES
            // $columns[][name]
            // $columns[][sub_part]
            // ...
            $_columns = $columns;
        }

        foreach ($_columns as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Returns true if $column indexed in this index
     *
     * @param string $column the column
     */
    public function hasColumn($column): bool
    {
        return isset($this->columns[$column]);
    }

    /**
     * Sets index details
     *
     * @param array $params index details
     */
    public function set(array $params): void
    {
        if (isset($params['columns'])) {
            $this->addColumns($params['columns']);
        }

        if (isset($params['Schema'])) {
            $this->schema = $params['Schema'];
        }

        if (isset($params['Table'])) {
            $this->table = $params['Table'];
        }

        if (isset($params['Key_name'])) {
            $this->name = $params['Key_name'];
        }

        if (isset($params['Index_type'])) {
            $this->type = $params['Index_type'];
        }

        if (isset($params['Comment'])) {
            $this->remarks = $params['Comment'];
        }

        if (isset($params['Index_comment'])) {
            $this->comment = $params['Index_comment'];
        }

        if (isset($params['Non_unique'])) {
            $this->nonUnique = $params['Non_unique'];
        }

        if (isset($params['Packed'])) {
            $this->packed = $params['Packed'];
        }

        if (isset($params['Index_choice'])) {
            $this->choice = $params['Index_choice'];
        } elseif ($this->name === 'PRIMARY') {
            $this->choice = 'PRIMARY';
        } elseif ($this->type === 'FULLTEXT') {
            $this->choice = 'FULLTEXT';
            $this->type = '';
        } elseif ($this->type === 'SPATIAL') {
            $this->choice = 'SPATIAL';
            $this->type = '';
        } elseif ($this->nonUnique == '0') {
            $this->choice = 'UNIQUE';
        } else {
            $this->choice = 'INDEX';
        }

        if (isset($params['Key_block_size'])) {
            $this->keyBlockSize = $params['Key_block_size'];
        }

        if (! isset($params['Parser'])) {
            return;
        }

        $this->parser = $params['Parser'];
    }

    /**
     * Returns the number of columns of the index
     *
     * @return int the number of the columns
     */
    public function getColumnCount()
    {
        return count($this->columns);
    }

    /**
     * Returns the index comment
     *
     * @return string index comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Returns index remarks
     *
     * @return string index remarks
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Return the key block size
     *
     * @return int
     */
    public function getKeyBlockSize()
    {
        return $this->keyBlockSize;
    }

    /**
     * Return the parser
     *
     * @return string
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Returns concatenated remarks and comment
     *
     * @return string concatenated remarks and comment
     */
    public function getComments()
    {
        $comments = $this->getRemarks();
        if (strlen($comments) > 0) {
            $comments .= "\n";
        }

        $comments .= $this->getComment();

        return $comments;
    }

    /**
     * Returns index type (BTREE, HASH, RTREE)
     *
     * @return string index type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns index choice (PRIMARY, UNIQUE, INDEX, SPATIAL, FULLTEXT)
     *
     * @return string index choice
     */
    public function getChoice()
    {
        return $this->choice;
    }

    /**
     * Returns a lit of all index types
     *
     * @return string[] index types
     */
    public static function getIndexTypes()
    {
        return [
            'BTREE',
            'HASH',
        ];
    }

    public function hasPrimary(): bool
    {
        return (bool) self::getPrimary($this->table, $this->schema);
    }

    /**
     * Returns how the index is packed
     *
     * @return string how the index is packed
     */
    public function getPacked()
    {
        return $this->packed;
    }

    /**
     * Returns 'No' if the index is not packed,
     * how the index is packed if packed
     *
     * @return string
     */
    public function isPacked()
    {
        if ($this->packed === null) {
            return __('No');
        }

        return htmlspecialchars($this->packed);
    }

    /**
     * Returns integer 0 if the index cannot contain duplicates, 1 if it can
     *
     * @return int 0 if the index cannot contain duplicates, 1 if it can
     */
    public function getNonUnique()
    {
        return $this->nonUnique;
    }

    /**
     * Returns whether the index is a 'Unique' index
     *
     * @param bool $as_text whether to output should be in text
     *
     * @return mixed whether the index is a 'Unique' index
     */
    public function isUnique($as_text = false)
    {
        if ($as_text) {
            $r = [
                '0' => __('Yes'),
                '1' => __('No'),
            ];
        } else {
            $r = [
                '0' => true,
                '1' => false,
            ];
        }

        return $r[$this->nonUnique];
    }

    /**
     * Returns the name of the index
     *
     * @return string the name of the index
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the index
     *
     * @param string $name index name
     */
    public function setName($name): void
    {
        $this->name = (string) $name;
    }

    /**
     * Returns the columns of the index
     *
     * @return IndexColumn[] the columns of the index
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Gets the properties in an array for comparison purposes
     *
     * @return array an array containing the properties of the index
     */
    public function getCompareData()
    {
        $data = [
            'Packed' => $this->packed,
            'Index_choice' => $this->choice,
        ];

        foreach ($this->columns as $column) {
            $data['columns'][] = $column->getCompareData();
        }

        return $data;
    }

    /**
     * Function to check over array of indexes and look for common problems
     *
     * @param string $table  table name
     * @param string $schema schema name
     *
     * @return string  Output HTML
     */
    public static function findDuplicates($table, $schema)
    {
        $indexes = self::getFromTable($table, $schema);

        $output = '';

        // count($indexes) < 2:
        //   there is no need to check if there less than two indexes
        if (count($indexes) < 2) {
            return $output;
        }

        // remove last index from stack and ...
        while ($while_index = array_pop($indexes)) {
            // ... compare with every remaining index in stack
            foreach ($indexes as $each_index) {
                if ($each_index->getCompareData() !== $while_index->getCompareData()) {
                    continue;
                }

                // did not find any difference
                // so it makes no sense to have this two equal indexes

                $message = Message::notice(
                    __(
                        'The indexes %1$s and %2$s seem to be equal and one of them could possibly be removed.'
                    )
                );
                $message->addParam($each_index->getName());
                $message->addParam($while_index->getName());
                $output .= $message->getDisplay();

                // there is no need to check any further indexes if we have already
                // found that this one has a duplicate
                continue 2;
            }
        }

        return $output;
    }
}
