<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;
use PhpMyAdmin\Utils\Gis;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UtilExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'backquote',
                [Util::class, 'backquote']
            ),
            new TwigFunction(
                'extract_column_spec',
                [Util::class, 'extractColumnSpec']
            ),
            new TwigFunction(
                'format_byte_down',
                [Util::class, 'formatByteDown']
            ),
            new TwigFunction(
                'format_number',
                [Util::class, 'formatNumber']
            ),
            new TwigFunction(
                'format_sql',
                [Generator::class, 'formatSql'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_docu_link',
                [MySQLDocumentation::class, 'getDocumentationLink'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_list_navigator',
                [Generator::class, 'getListNavigator'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_docu',
                [MySQLDocumentation::class, 'showDocumentation'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_gis_datatypes',
                [Gis::class, 'getDataTypes']
            ),
            new TwigFunction(
                'get_gis_functions',
                [Gis::class, 'getFunctions']
            ),
            new TwigFunction(
                'get_icon',
                [Generator::class, 'getIcon'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_image',
                [Generator::class, 'getImage'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_supported_datatypes',
                [Util::class, 'getSupportedDatatypes'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'is_uuid_supported',
                [Util::class, 'isUUIDSupported']
            ),
            new TwigFunction(
                'is_foreign_key_supported',
                [ForeignKey::class, 'isSupported']
            ),
            new TwigFunction(
                'link_or_button',
                [Generator::class, 'linkOrButton'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'link_to_var_documentation',
                [Generator::class, 'linkToVarDocumentation'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'localised_date',
                [Util::class, 'localisedDate']
            ),
            new TwigFunction(
                'show_hint',
                [Generator::class, 'showHint'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_icons',
                [Util::class, 'showIcons']
            ),
            new TwigFunction(
                'show_text',
                [Util::class, 'showText']
            ),
            new TwigFunction(
                'show_mysql_docu',
                [MySQLDocumentation::class, 'show'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_mysql_docu_url',
                [Util::class, 'getMySQLDocuURL'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_docu_url',
                [Util::class, 'getdocuURL'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_php_docu',
                [Generator::class, 'showPHPDocumentation'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'sortable_table_header',
                [Util::class, 'sortableTableHeader'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'timespan_format',
                [Util::class, 'timespanFormat']
            ),
            new TwigFunction('parse_enum_set_values', 'PhpMyAdmin\Util::parseEnumSetValues'),
        ];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'convert_bit_default_value',
                [Util::class, 'convertBitDefaultValue']
            ),
            new TwigFilter(
                'escape_mysql_wildcards',
                [Util::class, 'escapeMysqlWildcards']
            ),
        ];
    }
}
