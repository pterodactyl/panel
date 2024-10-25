<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function in_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 * @psalm-type StructureOrDataType = 'structure'|'data'|'structure_and_data'
 */
final class Export
{
    /**
     * @var string
     * @psalm-var 'codegen'|'csv'|'excel'|'htmlexcel'|'htmlword'|'latex'|'ods'|'odt'|'pdf'|'sql'|'texytext'|'xml'|'yaml'
     */
    public $format;

    /**
     * @var string
     * @psalm-var 'quick'|'custom'|'custom-no-form'
     */
    public $method;

    /**
     * @var string
     * @psalm-var 'none'|'zip'|'gzip'
     */
    public $compression;

    /**
     * Whether to LOCK TABLES before exporting
     *
     * @var bool
     */
    public $lock_tables;

    /**
     * Whether to export databases/tables as separate files
     *
     * @var bool
     */
    public $as_separate_files;

    /** @var bool */
    public $asfile;

    /** @var string */
    public $charset;

    /** @var bool */
    public $onserver;

    /** @var bool */
    public $onserver_overwrite;

    /** @var bool */
    public $quick_export_onserver;

    /** @var bool */
    public $quick_export_onserver_overwrite;

    /** @var bool */
    public $remember_file_template;

    /** @var string */
    public $file_template_table;

    /** @var string */
    public $file_template_database;

    /** @var string */
    public $file_template_server;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $codegen_structure_or_data;

    /**
     * @var int
     * @psalm-var 0|1
     */
    public $codegen_format;

    /** @var bool */
    public $ods_columns;

    /** @var string */
    public $ods_null;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $odt_structure_or_data;

    /** @var bool */
    public $odt_columns;

    /** @var bool */
    public $odt_relation;

    /** @var bool */
    public $odt_comments;

    /** @var bool */
    public $odt_mime;

    /** @var string */
    public $odt_null;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $htmlword_structure_or_data;

    /** @var bool */
    public $htmlword_columns;

    /** @var string */
    public $htmlword_null;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $texytext_structure_or_data;

    /** @var bool */
    public $texytext_columns;

    /** @var string */
    public $texytext_null;

    /** @var bool */
    public $csv_columns;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $csv_structure_or_data;

    /** @var string */
    public $csv_null;

    /** @var string */
    public $csv_separator;

    /** @var string */
    public $csv_enclosed;

    /** @var string */
    public $csv_escaped;

    /** @var string */
    public $csv_terminated;

    /** @var bool */
    public $csv_removeCRLF;

    /** @var bool */
    public $excel_columns;

    /** @var string */
    public $excel_null;

    /**
     * @var string
     * @psalm-var 'win'|'mac_excel2003'|'mac_excel2008'
     */
    public $excel_edition;

    /** @var bool */
    public $excel_removeCRLF;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $excel_structure_or_data;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $latex_structure_or_data;

    /** @var bool */
    public $latex_columns;

    /** @var bool */
    public $latex_relation;

    /** @var bool */
    public $latex_comments;

    /** @var bool */
    public $latex_mime;

    /** @var string */
    public $latex_null;

    /** @var bool */
    public $latex_caption;

    /** @var string */
    public $latex_structure_caption;

    /** @var string */
    public $latex_structure_continued_caption;

    /** @var string */
    public $latex_data_caption;

    /** @var string */
    public $latex_data_continued_caption;

    /** @var string */
    public $latex_data_label;

    /** @var string */
    public $latex_structure_label;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $mediawiki_structure_or_data;

    /** @var bool */
    public $mediawiki_caption;

    /** @var bool */
    public $mediawiki_headers;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $ods_structure_or_data;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $pdf_structure_or_data;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $phparray_structure_or_data;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $json_structure_or_data;

    /** @var bool */
    public $json_pretty_print;

    /** @var bool */
    public $json_unicode;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $sql_structure_or_data;

    /**
     * @var string
     * @psalm-var 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    public $sql_compatibility;

    /**
     * Whether to include comments in SQL export.
     *
     * @var bool
     */
    public $sql_include_comments;

    /** @var bool */
    public $sql_disable_fk;

    /** @var bool */
    public $sql_views_as_tables;

    /** @var bool */
    public $sql_metadata;

    /** @var bool */
    public $sql_use_transaction;

    /** @var bool */
    public $sql_create_database;

    /** @var bool */
    public $sql_drop_database;

    /** @var bool */
    public $sql_drop_table;

    /**
     * true by default for correct behavior when dealing with exporting
     * of VIEWs and the stand-in table
     *
     * @var bool
     */
    public $sql_if_not_exists;

    /** @var bool */
    public $sql_view_current_user;

    /** @var bool */
    public $sql_or_replace_view;

    /** @var bool */
    public $sql_procedure_function;

    /** @var bool */
    public $sql_create_table;

    /** @var bool */
    public $sql_create_view;

    /** @var bool */
    public $sql_create_trigger;

    /** @var bool */
    public $sql_auto_increment;

    /** @var bool */
    public $sql_backquotes;

    /** @var bool */
    public $sql_dates;

    /** @var bool */
    public $sql_relation;

    /** @var bool */
    public $sql_truncate;

    /** @var bool */
    public $sql_delayed;

    /** @var bool */
    public $sql_ignore;

    /**
     * Export time in UTC.
     *
     * @var bool
     */
    public $sql_utc_time;

    /** @var bool */
    public $sql_hex_for_binary;

    /**
     * @var string
     * @psalm-var 'INSERT'|'UPDATE'|'REPLACE'
     */
    public $sql_type;

    /**
     * @var int
     * @psalm-var 0|positive-int
     */
    public $sql_max_query_size;

    /** @var bool */
    public $sql_mime;

    /**
     * \n is replaced by new line
     *
     * @var string
     */
    public $sql_header_comment;

    /**
     * Whether to use complete inserts, extended inserts, both, or neither
     *
     * @var string
     * @psalm-var 'complete'|'extended'|'both'|'none'
     */
    public $sql_insert_syntax;

    /** @var string */
    public $pdf_report_title;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $xml_structure_or_data;

    /**
     * Export schema for each structure
     *
     * @var bool
     */
    public $xml_export_struc;

    /**
     * Export events
     *
     * @var bool
     */
    public $xml_export_events;

    /**
     * Export functions
     *
     * @var bool
     */
    public $xml_export_functions;

    /**
     * Export procedures
     *
     * @var bool
     */
    public $xml_export_procedures;

    /**
     * Export schema for each table
     *
     * @var bool
     */
    public $xml_export_tables;

    /**
     * Export triggers
     *
     * @var bool
     */
    public $xml_export_triggers;

    /**
     * Export views
     *
     * @var bool
     */
    public $xml_export_views;

    /**
     * Export contents data
     *
     * @var bool
     */
    public $xml_export_contents;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $yaml_structure_or_data;

    /** @var bool */
    public $remove_definer_from_definitions;

    /**
     * @param array<int|string, mixed> $export
     */
    public function __construct(array $export = [])
    {
        $this->format = $this->setFormat($export);
        $this->method = $this->setMethod($export);
        $this->compression = $this->setCompression($export);
        $this->lock_tables = $this->setLockTables($export);
        $this->as_separate_files = $this->setAsSeparateFiles($export);
        $this->asfile = $this->setAsFile($export);
        $this->charset = $this->setCharset($export);
        $this->onserver = $this->setOnServer($export);
        $this->onserver_overwrite = $this->setOnServerOverwrite($export);
        $this->quick_export_onserver = $this->setQuickExportOnServer($export);
        $this->quick_export_onserver_overwrite = $this->setQuickExportOnServerOverwrite($export);
        $this->remember_file_template = $this->setRememberFileTemplate($export);
        $this->file_template_table = $this->setFileTemplateTable($export);
        $this->file_template_database = $this->setFileTemplateDatabase($export);
        $this->file_template_server = $this->setFileTemplateServer($export);
        $this->codegen_structure_or_data = $this->setCodegenStructureOrData($export);
        $this->codegen_format = $this->setCodegenFormat($export);
        $this->ods_columns = $this->setOdsColumns($export);
        $this->ods_null = $this->setOdsNull($export);
        $this->odt_structure_or_data = $this->setOdtStructureOrData($export);
        $this->odt_columns = $this->setOdtColumns($export);
        $this->odt_relation = $this->setOdtRelation($export);
        $this->odt_comments = $this->setOdtComments($export);
        $this->odt_mime = $this->setOdtMime($export);
        $this->odt_null = $this->setOdtNull($export);
        $this->htmlword_structure_or_data = $this->setHtmlwordStructureOrData($export);
        $this->htmlword_columns = $this->setHtmlwordColumns($export);
        $this->htmlword_null = $this->setHtmlwordNull($export);
        $this->texytext_structure_or_data = $this->setTexytextStructureOrData($export);
        $this->texytext_columns = $this->setTexytextColumns($export);
        $this->texytext_null = $this->setTexytextNull($export);
        $this->csv_columns = $this->setCsvColumns($export);
        $this->csv_structure_or_data = $this->setCsvStructureOrData($export);
        $this->csv_null = $this->setCsvNull($export);
        $this->csv_separator = $this->setCsvSeparator($export);
        $this->csv_enclosed = $this->setCsvEnclosed($export);
        $this->csv_escaped = $this->setCsvEscaped($export);
        $this->csv_terminated = $this->setCsvTerminated($export);
        $this->csv_removeCRLF = $this->setCsvRemoveCRLF($export);
        $this->excel_columns = $this->setExcelColumns($export);
        $this->excel_null = $this->setExcelNull($export);
        $this->excel_edition = $this->setExcelEdition($export);
        $this->excel_removeCRLF = $this->setExcelRemoveCRLF($export);
        $this->excel_structure_or_data = $this->setExcelStructureOrData($export);
        $this->latex_structure_or_data = $this->setLatexStructureOrData($export);
        $this->latex_columns = $this->setLatexColumns($export);
        $this->latex_relation = $this->setLatexRelation($export);
        $this->latex_comments = $this->setLatexComments($export);
        $this->latex_mime = $this->setLatexMime($export);
        $this->latex_null = $this->setLatexNull($export);
        $this->latex_caption = $this->setLatexCaption($export);
        $this->latex_structure_caption = $this->setLatexStructureCaption($export);
        $this->latex_structure_continued_caption = $this->setLatexStructureContinuedCaption($export);
        $this->latex_data_caption = $this->setLatexDataCaption($export);
        $this->latex_data_continued_caption = $this->setLatexDataContinuedCaption($export);
        $this->latex_data_label = $this->setLatexDataLabel($export);
        $this->latex_structure_label = $this->setLatexStructureLabel($export);
        $this->mediawiki_structure_or_data = $this->setMediawikiStructureOrData($export);
        $this->mediawiki_caption = $this->setMediawikiCaption($export);
        $this->mediawiki_headers = $this->setMediawikiHeaders($export);
        $this->ods_structure_or_data = $this->setOdsStructureOrData($export);
        $this->pdf_structure_or_data = $this->setPdfStructureOrData($export);
        $this->phparray_structure_or_data = $this->setPhparrayStructureOrData($export);
        $this->json_structure_or_data = $this->setJsonStructureOrData($export);
        $this->json_pretty_print = $this->setJsonPrettyPrint($export);
        $this->json_unicode = $this->setJsonUnicode($export);
        $this->sql_structure_or_data = $this->setSqlStructureOrData($export);
        $this->sql_compatibility = $this->setSqlCompatibility($export);
        $this->sql_include_comments = $this->setSqlIncludeComments($export);
        $this->sql_disable_fk = $this->setSqlDisableFk($export);
        $this->sql_views_as_tables = $this->setSqlViewsAsTables($export);
        $this->sql_metadata = $this->setSqlMetadata($export);
        $this->sql_use_transaction = $this->setSqlUseTransaction($export);
        $this->sql_create_database = $this->setSqlCreateDatabase($export);
        $this->sql_drop_database = $this->setSqlDropDatabase($export);
        $this->sql_drop_table = $this->setSqlDropTable($export);
        $this->sql_if_not_exists = $this->setSqlIfNotExists($export);
        $this->sql_view_current_user = $this->setSqlViewCurrentUser($export);
        $this->sql_or_replace_view = $this->setSqlOrReplaceView($export);
        $this->sql_procedure_function = $this->setSqlProcedureFunction($export);
        $this->sql_create_table = $this->setSqlCreateTable($export);
        $this->sql_create_view = $this->setSqlCreateView($export);
        $this->sql_create_trigger = $this->setSqlCreateTrigger($export);
        $this->sql_auto_increment = $this->setSqlAutoIncrement($export);
        $this->sql_backquotes = $this->setSqlBackquotes($export);
        $this->sql_dates = $this->setSqlDates($export);
        $this->sql_relation = $this->setSqlRelation($export);
        $this->sql_truncate = $this->setSqlTruncate($export);
        $this->sql_delayed = $this->setSqlDelayed($export);
        $this->sql_ignore = $this->setSqlIgnore($export);
        $this->sql_utc_time = $this->setSqlUtcTime($export);
        $this->sql_hex_for_binary = $this->setSqlHexForBinary($export);
        $this->sql_type = $this->setSqlType($export);
        $this->sql_max_query_size = $this->setSqlMaxQuerySize($export);
        $this->sql_mime = $this->setSqlMime($export);
        $this->sql_header_comment = $this->setSqlHeaderComment($export);
        $this->sql_insert_syntax = $this->setSqlInsertSyntax($export);
        $this->pdf_report_title = $this->setPdfReportTitle($export);
        $this->xml_structure_or_data = $this->setXmlStructureOrData($export);
        $this->xml_export_struc = $this->setXmlExportStruc($export);
        $this->xml_export_events = $this->setXmlExportEvents($export);
        $this->xml_export_functions = $this->setXmlExportFunctions($export);
        $this->xml_export_procedures = $this->setXmlExportProcedures($export);
        $this->xml_export_tables = $this->setXmlExportTables($export);
        $this->xml_export_triggers = $this->setXmlExportTriggers($export);
        $this->xml_export_views = $this->setXmlExportViews($export);
        $this->xml_export_contents = $this->setXmlExportContents($export);
        $this->yaml_structure_or_data = $this->setYamlStructureOrData($export);
        $this->remove_definer_from_definitions = $this->setRemoveDefinerClause($export);
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'codegen'|'csv'|'excel'|'htmlexcel'|'htmlword'|'latex'|'ods'|'odt'|'pdf'|'sql'|'texytext'|'xml'|'yaml'
     */
    private function setFormat(array $export): string
    {
        if (
            ! isset($export['format']) || ! in_array($export['format'], [
                'codegen',
                'csv',
                'excel',
                'htmlexcel',
                'htmlword',
                'latex',
                'ods',
                'odt',
                'pdf',
                'texytext',
                'xml',
                'yaml',
            ], true)
        ) {
            return 'sql';
        }

        return $export['format'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'quick'|'custom'|'custom-no-form'
     */
    private function setMethod(array $export): string
    {
        if (! isset($export['method']) || ! in_array($export['method'], ['custom', 'custom-no-form'], true)) {
            return 'quick';
        }

        return $export['method'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'none'|'zip'|'gzip'
     */
    private function setCompression(array $export): string
    {
        if (! isset($export['compression']) || ! in_array($export['compression'], ['zip', 'gzip'], true)) {
            return 'none';
        }

        return $export['compression'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLockTables(array $export): bool
    {
        if (! isset($export['lock_tables'])) {
            return false;
        }

        return (bool) $export['lock_tables'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setAsSeparateFiles(array $export): bool
    {
        if (! isset($export['as_separate_files'])) {
            return false;
        }

        return (bool) $export['as_separate_files'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setAsFile(array $export): bool
    {
        if (! isset($export['asfile'])) {
            return true;
        }

        return (bool) $export['asfile'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCharset(array $export): string
    {
        if (! isset($export['charset'])) {
            return '';
        }

        return (string) $export['charset'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOnServer(array $export): bool
    {
        if (! isset($export['onserver'])) {
            return false;
        }

        return (bool) $export['onserver'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOnServerOverwrite(array $export): bool
    {
        if (! isset($export['onserver_overwrite'])) {
            return false;
        }

        return (bool) $export['onserver_overwrite'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setQuickExportOnServer(array $export): bool
    {
        if (! isset($export['quick_export_onserver'])) {
            return false;
        }

        return (bool) $export['quick_export_onserver'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setQuickExportOnServerOverwrite(array $export): bool
    {
        if (! isset($export['quick_export_onserver_overwrite'])) {
            return false;
        }

        return (bool) $export['quick_export_onserver_overwrite'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setRememberFileTemplate(array $export): bool
    {
        if (! isset($export['remember_file_template'])) {
            return true;
        }

        return (bool) $export['remember_file_template'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setFileTemplateTable(array $export): string
    {
        if (! isset($export['file_template_table'])) {
            return '@TABLE@';
        }

        return (string) $export['file_template_table'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setFileTemplateDatabase(array $export): string
    {
        if (! isset($export['file_template_database'])) {
            return '@DATABASE@';
        }

        return (string) $export['file_template_database'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setFileTemplateServer(array $export): string
    {
        if (! isset($export['file_template_server'])) {
            return '@SERVER@';
        }

        return (string) $export['file_template_server'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setCodegenStructureOrData(array $export): string
    {
        if (
            ! isset($export['codegen_structure_or_data'])
            || ! in_array($export['codegen_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['codegen_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 0|1
     */
    private function setCodegenFormat(array $export): int
    {
        if (! isset($export['codegen_format'])) {
            return 0;
        }

        $codegenFormat = (int) $export['codegen_format'];

        return $codegenFormat === 1 ? 1 : 0;
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOdsColumns(array $export): bool
    {
        if (! isset($export['ods_columns'])) {
            return false;
        }

        return (bool) $export['ods_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOdsNull(array $export): string
    {
        if (! isset($export['ods_null'])) {
            return 'NULL';
        }

        return (string) $export['ods_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setOdtStructureOrData(array $export): string
    {
        if (
            ! isset($export['odt_structure_or_data'])
            || ! in_array($export['odt_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['odt_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOdtColumns(array $export): bool
    {
        if (! isset($export['odt_columns'])) {
            return true;
        }

        return (bool) $export['odt_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOdtRelation(array $export): bool
    {
        if (! isset($export['odt_relation'])) {
            return true;
        }

        return (bool) $export['odt_relation'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOdtComments(array $export): bool
    {
        if (! isset($export['odt_comments'])) {
            return true;
        }

        return (bool) $export['odt_comments'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOdtMime(array $export): bool
    {
        if (! isset($export['odt_mime'])) {
            return true;
        }

        return (bool) $export['odt_mime'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setOdtNull(array $export): string
    {
        if (! isset($export['odt_null'])) {
            return 'NULL';
        }

        return (string) $export['odt_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setHtmlwordStructureOrData(array $export): string
    {
        if (
            ! isset($export['htmlword_structure_or_data'])
            || ! in_array($export['htmlword_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['htmlword_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setHtmlwordColumns(array $export): bool
    {
        if (! isset($export['htmlword_columns'])) {
            return false;
        }

        return (bool) $export['htmlword_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setHtmlwordNull(array $export): string
    {
        if (! isset($export['htmlword_null'])) {
            return 'NULL';
        }

        return (string) $export['htmlword_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setTexytextStructureOrData(array $export): string
    {
        if (
            ! isset($export['texytext_structure_or_data'])
            || ! in_array($export['texytext_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['texytext_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setTexytextColumns(array $export): bool
    {
        if (! isset($export['texytext_columns'])) {
            return false;
        }

        return (bool) $export['texytext_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setTexytextNull(array $export): string
    {
        if (! isset($export['texytext_null'])) {
            return 'NULL';
        }

        return (string) $export['texytext_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCsvColumns(array $export): bool
    {
        if (! isset($export['csv_columns'])) {
            return true;
        }

        return (bool) $export['csv_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setCsvStructureOrData(array $export): string
    {
        if (
            ! isset($export['csv_structure_or_data'])
            || ! in_array($export['csv_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['csv_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCsvNull(array $export): string
    {
        if (! isset($export['csv_null'])) {
            return 'NULL';
        }

        return (string) $export['csv_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCsvSeparator(array $export): string
    {
        if (! isset($export['csv_separator'])) {
            return ',';
        }

        return (string) $export['csv_separator'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCsvEnclosed(array $export): string
    {
        if (! isset($export['csv_enclosed'])) {
            return '"';
        }

        return (string) $export['csv_enclosed'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCsvEscaped(array $export): string
    {
        if (! isset($export['csv_escaped'])) {
            return '"';
        }

        return (string) $export['csv_escaped'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCsvTerminated(array $export): string
    {
        if (! isset($export['csv_terminated'])) {
            return 'AUTO';
        }

        return (string) $export['csv_terminated'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setCsvRemoveCRLF(array $export): bool
    {
        if (! isset($export['csv_removeCRLF'])) {
            return false;
        }

        return (bool) $export['csv_removeCRLF'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setExcelColumns(array $export): bool
    {
        if (! isset($export['excel_columns'])) {
            return true;
        }

        return (bool) $export['excel_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setExcelNull(array $export): string
    {
        if (! isset($export['excel_null'])) {
            return 'NULL';
        }

        return (string) $export['excel_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'win'|'mac_excel2003'|'mac_excel2008'
     */
    private function setExcelEdition(array $export): string
    {
        if (
            ! isset($export['excel_edition'])
            || ! in_array($export['excel_edition'], ['mac_excel2003', 'mac_excel2008'], true)
        ) {
            return 'win';
        }

        return $export['excel_edition'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setExcelRemoveCRLF(array $export): bool
    {
        if (! isset($export['excel_removeCRLF'])) {
            return false;
        }

        return (bool) $export['excel_removeCRLF'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setExcelStructureOrData(array $export): string
    {
        if (
            ! isset($export['excel_structure_or_data'])
            || ! in_array($export['excel_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['excel_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setLatexStructureOrData(array $export): string
    {
        if (
            ! isset($export['latex_structure_or_data'])
            || ! in_array($export['latex_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['latex_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexColumns(array $export): bool
    {
        if (! isset($export['latex_columns'])) {
            return true;
        }

        return (bool) $export['latex_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexRelation(array $export): bool
    {
        if (! isset($export['latex_relation'])) {
            return true;
        }

        return (bool) $export['latex_relation'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexComments(array $export): bool
    {
        if (! isset($export['latex_comments'])) {
            return true;
        }

        return (bool) $export['latex_comments'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexMime(array $export): bool
    {
        if (! isset($export['latex_mime'])) {
            return true;
        }

        return (bool) $export['latex_mime'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexNull(array $export): string
    {
        if (! isset($export['latex_null'])) {
            return '\textit{NULL}';
        }

        return (string) $export['latex_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexCaption(array $export): bool
    {
        if (! isset($export['latex_caption'])) {
            return true;
        }

        return (bool) $export['latex_caption'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexStructureCaption(array $export): string
    {
        if (! isset($export['latex_structure_caption'])) {
            return 'strLatexStructure';
        }

        return (string) $export['latex_structure_caption'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexStructureContinuedCaption(array $export): string
    {
        if (! isset($export['latex_structure_continued_caption'])) {
            return 'strLatexStructure strLatexContinued';
        }

        return (string) $export['latex_structure_continued_caption'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexDataCaption(array $export): string
    {
        if (! isset($export['latex_data_caption'])) {
            return 'strLatexContent';
        }

        return (string) $export['latex_data_caption'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexDataContinuedCaption(array $export): string
    {
        if (! isset($export['latex_data_continued_caption'])) {
            return 'strLatexContent strLatexContinued';
        }

        return (string) $export['latex_data_continued_caption'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexDataLabel(array $export): string
    {
        if (! isset($export['latex_data_label'])) {
            return 'tab:@TABLE@-data';
        }

        return (string) $export['latex_data_label'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setLatexStructureLabel(array $export): string
    {
        if (! isset($export['latex_structure_label'])) {
            return 'tab:@TABLE@-structure';
        }

        return (string) $export['latex_structure_label'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setMediawikiStructureOrData(array $export): string
    {
        if (
            ! isset($export['mediawiki_structure_or_data'])
            || ! in_array($export['mediawiki_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['mediawiki_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setMediawikiCaption(array $export): bool
    {
        if (! isset($export['mediawiki_caption'])) {
            return true;
        }

        return (bool) $export['mediawiki_caption'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setMediawikiHeaders(array $export): bool
    {
        if (! isset($export['mediawiki_headers'])) {
            return true;
        }

        return (bool) $export['mediawiki_headers'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setOdsStructureOrData(array $export): string
    {
        if (
            ! isset($export['ods_structure_or_data'])
            || ! in_array($export['ods_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['ods_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setPdfStructureOrData(array $export): string
    {
        if (
            ! isset($export['pdf_structure_or_data'])
            || ! in_array($export['pdf_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['pdf_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setPhparrayStructureOrData(array $export): string
    {
        if (
            ! isset($export['phparray_structure_or_data'])
            || ! in_array($export['phparray_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['phparray_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setJsonStructureOrData(array $export): string
    {
        if (
            ! isset($export['json_structure_or_data'])
            || ! in_array($export['json_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['json_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setJsonPrettyPrint(array $export): bool
    {
        if (! isset($export['json_pretty_print'])) {
            return false;
        }

        return (bool) $export['json_pretty_print'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setJsonUnicode(array $export): bool
    {
        if (! isset($export['json_unicode'])) {
            return true;
        }

        return (bool) $export['json_unicode'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setSqlStructureOrData(array $export): string
    {
        if (
            ! isset($export['sql_structure_or_data'])
            || ! in_array($export['sql_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['sql_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    private function setSqlCompatibility(array $export): string
    {
        if (
            ! isset($export['sql_compatibility']) || ! in_array($export['sql_compatibility'], [
                'ANSI',
                'DB2',
                'MAXDB',
                'MYSQL323',
                'MYSQL40',
                'MSSQL',
                'ORACLE',
                'TRADITIONAL',
            ], true)
        ) {
            return 'NONE';
        }

        return $export['sql_compatibility'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlIncludeComments(array $export): bool
    {
        if (! isset($export['sql_include_comments'])) {
            return true;
        }

        return (bool) $export['sql_include_comments'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlDisableFk(array $export): bool
    {
        if (! isset($export['sql_disable_fk'])) {
            return false;
        }

        return (bool) $export['sql_disable_fk'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlViewsAsTables(array $export): bool
    {
        if (! isset($export['sql_views_as_tables'])) {
            return false;
        }

        return (bool) $export['sql_views_as_tables'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlMetadata(array $export): bool
    {
        if (! isset($export['sql_metadata'])) {
            return false;
        }

        return (bool) $export['sql_metadata'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlUseTransaction(array $export): bool
    {
        if (! isset($export['sql_use_transaction'])) {
            return true;
        }

        return (bool) $export['sql_use_transaction'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlCreateDatabase(array $export): bool
    {
        if (! isset($export['sql_create_database'])) {
            return false;
        }

        return (bool) $export['sql_create_database'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlDropDatabase(array $export): bool
    {
        if (! isset($export['sql_drop_database'])) {
            return false;
        }

        return (bool) $export['sql_drop_database'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlDropTable(array $export): bool
    {
        if (! isset($export['sql_drop_table'])) {
            return false;
        }

        return (bool) $export['sql_drop_table'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlIfNotExists(array $export): bool
    {
        if (! isset($export['sql_if_not_exists'])) {
            return false;
        }

        return (bool) $export['sql_if_not_exists'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlViewCurrentUser(array $export): bool
    {
        if (! isset($export['sql_view_current_user'])) {
            return false;
        }

        return (bool) $export['sql_view_current_user'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlOrReplaceView(array $export): bool
    {
        if (! isset($export['sql_or_replace_view'])) {
            return false;
        }

        return (bool) $export['sql_or_replace_view'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlProcedureFunction(array $export): bool
    {
        if (! isset($export['sql_procedure_function'])) {
            return true;
        }

        return (bool) $export['sql_procedure_function'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlCreateTable(array $export): bool
    {
        if (! isset($export['sql_create_table'])) {
            return true;
        }

        return (bool) $export['sql_create_table'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlCreateView(array $export): bool
    {
        if (! isset($export['sql_create_view'])) {
            return true;
        }

        return (bool) $export['sql_create_view'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlCreateTrigger(array $export): bool
    {
        if (! isset($export['sql_create_trigger'])) {
            return true;
        }

        return (bool) $export['sql_create_trigger'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlAutoIncrement(array $export): bool
    {
        if (! isset($export['sql_auto_increment'])) {
            return true;
        }

        return (bool) $export['sql_auto_increment'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlBackquotes(array $export): bool
    {
        if (! isset($export['sql_backquotes'])) {
            return true;
        }

        return (bool) $export['sql_backquotes'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlDates(array $export): bool
    {
        if (! isset($export['sql_dates'])) {
            return false;
        }

        return (bool) $export['sql_dates'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlRelation(array $export): bool
    {
        if (! isset($export['sql_relation'])) {
            return false;
        }

        return (bool) $export['sql_relation'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlTruncate(array $export): bool
    {
        if (! isset($export['sql_truncate'])) {
            return false;
        }

        return (bool) $export['sql_truncate'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlDelayed(array $export): bool
    {
        if (! isset($export['sql_delayed'])) {
            return false;
        }

        return (bool) $export['sql_delayed'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlIgnore(array $export): bool
    {
        if (! isset($export['sql_ignore'])) {
            return false;
        }

        return (bool) $export['sql_ignore'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlUtcTime(array $export): bool
    {
        if (! isset($export['sql_utc_time'])) {
            return true;
        }

        return (bool) $export['sql_utc_time'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlHexForBinary(array $export): bool
    {
        if (! isset($export['sql_hex_for_binary'])) {
            return true;
        }

        return (bool) $export['sql_hex_for_binary'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'INSERT'|'UPDATE'|'REPLACE'
     */
    private function setSqlType(array $export): string
    {
        if (! isset($export['sql_type']) || ! in_array($export['sql_type'], ['UPDATE', 'REPLACE'], true)) {
            return 'INSERT';
        }

        return $export['sql_type'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 0|positive-int
     */
    private function setSqlMaxQuerySize(array $export): int
    {
        if (! isset($export['sql_max_query_size'])) {
            return 50000;
        }

        $maxQuerySize = (int) $export['sql_max_query_size'];

        return $maxQuerySize >= 0 ? $maxQuerySize : 50000;
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlMime(array $export): bool
    {
        if (! isset($export['sql_mime'])) {
            return false;
        }

        return (bool) $export['sql_mime'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setSqlHeaderComment(array $export): string
    {
        if (! isset($export['sql_header_comment'])) {
            return '';
        }

        return (string) $export['sql_header_comment'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'complete'|'extended'|'both'|'none'
     */
    private function setSqlInsertSyntax(array $export): string
    {
        if (
            ! isset($export['sql_insert_syntax'])
            || ! in_array($export['sql_insert_syntax'], ['complete', 'extended', 'none'], true)
        ) {
            return 'both';
        }

        return $export['sql_insert_syntax'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setPdfReportTitle(array $export): string
    {
        if (! isset($export['pdf_report_title'])) {
            return '';
        }

        return (string) $export['pdf_report_title'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setXmlStructureOrData(array $export): string
    {
        if (
            ! isset($export['xml_structure_or_data'])
            || ! in_array($export['xml_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['xml_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportStruc(array $export): bool
    {
        if (! isset($export['xml_export_struc'])) {
            return true;
        }

        return (bool) $export['xml_export_struc'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportEvents(array $export): bool
    {
        if (! isset($export['xml_export_events'])) {
            return true;
        }

        return (bool) $export['xml_export_events'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportFunctions(array $export): bool
    {
        if (! isset($export['xml_export_functions'])) {
            return true;
        }

        return (bool) $export['xml_export_functions'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportProcedures(array $export): bool
    {
        if (! isset($export['xml_export_procedures'])) {
            return true;
        }

        return (bool) $export['xml_export_procedures'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportTables(array $export): bool
    {
        if (! isset($export['xml_export_tables'])) {
            return true;
        }

        return (bool) $export['xml_export_tables'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportTriggers(array $export): bool
    {
        if (! isset($export['xml_export_triggers'])) {
            return true;
        }

        return (bool) $export['xml_export_triggers'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportViews(array $export): bool
    {
        if (! isset($export['xml_export_views'])) {
            return true;
        }

        return (bool) $export['xml_export_views'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setXmlExportContents(array $export): bool
    {
        if (! isset($export['xml_export_contents'])) {
            return true;
        }

        return (bool) $export['xml_export_contents'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setYamlStructureOrData(array $export): string
    {
        if (
            ! isset($export['yaml_structure_or_data'])
            || ! in_array($export['yaml_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['yaml_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     */
    private function setRemoveDefinerClause(array $export): bool
    {
        if (! isset($export['remove_definer_from_definitions'])) {
            return false;
        }

        return (bool) $export['remove_definer_from_definitions'];
    }
}
