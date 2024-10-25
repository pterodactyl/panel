<?php
/**
 * Set of functions used to build NHibernate dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Plugins\Export\Helpers\TableProperty;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function implode;
use function preg_match;
use function preg_replace;
use function sprintf;
use function ucfirst;

/**
 * Handles the export for the CodeGen class
 */
class ExportCodegen extends ExportPlugin
{
    /**
     * CodeGen Formats
     *
     * @var array
     */
    private $cgFormats;

    private const HANDLER_NHIBERNATE_CS = 0;
    private const HANDLER_NHIBERNATE_XML = 1;

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'codegen';
    }

    /**
     * Initialize the local variables that are used for export CodeGen.
     */
    protected function init(): void
    {
        $this->setCgFormats([
            self::HANDLER_NHIBERNATE_CS => 'NHibernate C# DO',
            self::HANDLER_NHIBERNATE_XML => 'NHibernate XML',
        ]);
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('CodeGen');
        $exportPluginProperties->setExtension('cs');
        $exportPluginProperties->setMimeType('text/cs');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem('structure_or_data');
        $generalOptions->addProperty($leaf);
        $leaf = new SelectPropertyItem(
            'format',
            __('Format:')
        );
        $leaf->setValues($this->getCgFormats());
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter($db): bool
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    public function exportDBCreate($db, $exportType, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $crlf     the end of line sequence
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery SQL query for obtaining data
     * @param array  $aliases  Aliases of db/table/columns
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $sqlQuery,
        array $aliases = []
    ): bool {
        $format = (int) $GLOBALS['codegen_format'];

        if ($format === self::HANDLER_NHIBERNATE_CS) {
            return $this->export->outputHandler($this->handleNHibernateCSBody($db, $table, $crlf, $aliases));
        }

        if ($format === self::HANDLER_NHIBERNATE_XML) {
            return $this->export->outputHandler($this->handleNHibernateXMLBody($db, $table, $crlf, $aliases));
        }

        return $this->export->outputHandler(sprintf('%s is not supported.', $format));
    }

    /**
     * Used to make identifiers (from table or database names)
     *
     * @param string $str     name to be converted
     * @param bool   $ucfirst whether to make the first character uppercase
     *
     * @return string identifier
     */
    public static function cgMakeIdentifier($str, $ucfirst = true)
    {
        // remove unsafe characters
        $str = (string) preg_replace('/[^\p{L}\p{Nl}_]/u', '', $str);
        // make sure first character is a letter or _
        if (! preg_match('/^\pL/u', $str)) {
            $str = '_' . $str;
        }

        if ($ucfirst) {
            $str = ucfirst($str);
        }

        return $str;
    }

    /**
     * C# Handler
     *
     * @param string $db      database name
     * @param string $table   table name
     * @param string $crlf    line separator
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return string containing C# code lines, separated by "\n"
     */
    private function handleNHibernateCSBody($db, $table, $crlf, array $aliases = [])
    {
        global $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        $result = $dbi->query(
            sprintf(
                'DESC %s.%s',
                Util::backquote($db),
                Util::backquote($table)
            )
        );

        /** @var TableProperty[] $tableProperties */
        $tableProperties = [];
        while ($row = $result->fetchRow()) {
            $col_as = $this->getAlias($aliases, $row[0], 'col', $db, $table);
            if (! empty($col_as)) {
                $row[0] = $col_as;
            }

            $tableProperties[] = new TableProperty($row);
        }

        unset($result);

        $lines = [];
        $lines[] = 'using System;';
        $lines[] = 'using System.Collections;';
        $lines[] = 'using System.Collections.Generic;';
        $lines[] = 'using System.Text;';
        $lines[] = 'namespace ' . self::cgMakeIdentifier($db_alias);
        $lines[] = '{';
        $lines[] = '    #region '
            . self::cgMakeIdentifier($table_alias);
        $lines[] = '    public class '
            . self::cgMakeIdentifier($table_alias);
        $lines[] = '    {';
        $lines[] = '        #region Member Variables';
        foreach ($tableProperties as $tableProperty) {
            $lines[] = $tableProperty->formatCs('        protected #dotNetPrimitiveType# _#name#;');
        }

        $lines[] = '        #endregion';
        $lines[] = '        #region Constructors';
        $lines[] = '        public '
            . self::cgMakeIdentifier($table_alias) . '() { }';
        $temp = [];
        foreach ($tableProperties as $tableProperty) {
            if ($tableProperty->isPK()) {
                continue;
            }

            $temp[] = $tableProperty->formatCs('#dotNetPrimitiveType# #name#');
        }

        $lines[] = '        public '
            . self::cgMakeIdentifier($table_alias)
            . '('
            . implode(', ', $temp)
            . ')';
        $lines[] = '        {';
        foreach ($tableProperties as $tableProperty) {
            if ($tableProperty->isPK()) {
                continue;
            }

            $lines[] = $tableProperty->formatCs('            this._#name#=#name#;');
        }

        $lines[] = '        }';
        $lines[] = '        #endregion';
        $lines[] = '        #region Public Properties';
        foreach ($tableProperties as $tableProperty) {
            $lines[] = $tableProperty->formatCs(
                '        public virtual #dotNetPrimitiveType# #ucfirstName#'
                . "\n"
                . '        {' . "\n"
                . '            get {return _#name#;}' . "\n"
                . '            set {_#name#=value;}' . "\n"
                . '        }'
            );
        }

        $lines[] = '        #endregion';
        $lines[] = '    }';
        $lines[] = '    #endregion';
        $lines[] = '}';

        return implode($crlf, $lines);
    }

    /**
     * XML Handler
     *
     * @param string $db      database name
     * @param string $table   table name
     * @param string $crlf    line separator
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return string containing XML code lines, separated by "\n"
     */
    private function handleNHibernateXMLBody(
        $db,
        $table,
        $crlf,
        array $aliases = []
    ) {
        global $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);
        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="utf-8" ?>';
        $lines[] = '<hibernate-mapping xmlns="urn:nhibernate-mapping-2.2" '
            . 'namespace="' . self::cgMakeIdentifier($db_alias) . '" '
            . 'assembly="' . self::cgMakeIdentifier($db_alias) . '">';
        $lines[] = '    <class '
            . 'name="' . self::cgMakeIdentifier($table_alias) . '" '
            . 'table="' . self::cgMakeIdentifier($table_alias) . '">';
        $result = $dbi->query(
            sprintf(
                'DESC %s.%s',
                Util::backquote($db),
                Util::backquote($table)
            )
        );

        while ($row = $result->fetchRow()) {
            $col_as = $this->getAlias($aliases, $row[0], 'col', $db, $table);
            if (! empty($col_as)) {
                $row[0] = $col_as;
            }

            $tableProperty = new TableProperty($row);
            if ($tableProperty->isPK()) {
                $lines[] = $tableProperty->formatXml(
                    '        <id name="#ucfirstName#" type="#dotNetObjectType#"'
                    . ' unsaved-value="0">' . "\n"
                    . '            <column name="#name#" sql-type="#type#"'
                    . ' not-null="#notNull#" unique="#unique#"'
                    . ' index="PRIMARY"/>' . "\n"
                    . '            <generator class="native" />' . "\n"
                    . '        </id>'
                );
            } else {
                $lines[] = $tableProperty->formatXml(
                    '        <property name="#ucfirstName#"'
                    . ' type="#dotNetObjectType#">' . "\n"
                    . '            <column name="#name#" sql-type="#type#"'
                    . ' not-null="#notNull#" #indexName#/>' . "\n"
                    . '        </property>'
                );
            }
        }

        $lines[] = '    </class>';
        $lines[] = '</hibernate-mapping>';

        return implode($crlf, $lines);
    }

    /**
     * Getter for CodeGen formats
     *
     * @return array
     */
    private function getCgFormats()
    {
        return $this->cgFormats;
    }

    /**
     * Setter for CodeGen formats
     *
     * @param array $CG_FORMATS contains CodeGen Formats
     */
    private function setCgFormats(array $CG_FORMATS): void
    {
        $this->cgFormats = $CG_FORMATS;
    }
}
