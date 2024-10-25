<?php
/**
 * Verbose descriptions for settings.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Sanitize;

use function __;
use function sprintf;
use function str_replace;

/**
 * Class to get name or description for a configuration paths
 */
class Descriptions
{
    /**
     * Return name or description for a configuration path.
     *
     * @param string $path Path of configuration
     * @param string $type Type of message, either 'name', 'cmt' or 'desc'
     *
     * @return string
     */
    public static function get($path, $type = 'name')
    {
        $key = str_replace(
            [
                'Servers/1/',
                '/',
            ],
            [
                'Servers/',
                '_',
            ],
            $path
        );
        $value = self::getString($key, $type);

        /* Fallback to path for name and empty string for description and comment */
        if ($value === null) {
            if ($type === 'name') {
                $value = $path;
            } else {
                $value = '';
            }
        }

        return Sanitize::sanitizeMessage($value);
    }

    /**
     * @return array<string, string>
     */
    private static function getComments(): array
    {
        /** @var array<string, string> $commentsMap */
        static $commentsMap = [];

        if ($commentsMap !== []) {
            return $commentsMap;
        }

        return $commentsMap = [
            'MaxDbList_cmt' => __('Users cannot set a higher value'),
            'MaxTableList_cmt' => __('Users cannot set a higher value'),
            'QueryHistoryMax_cmt' => __('Users cannot set a higher value'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function getDescriptions(): array
    {
        /** @var array<string, string> $descriptionsMap */
        static $descriptionsMap = [];

        if ($descriptionsMap !== []) {
            return $descriptionsMap;
        }

        return $descriptionsMap = [
            'AllowArbitraryServer_desc' => __(
                'If enabled, user can enter any MySQL server in login form for cookie auth.'
            ),
            'ArbitraryServerRegexp_desc' => __(
                'Restricts the MySQL servers the user can enter when a login to an arbitrary '
                . 'MySQL server is enabled by matching the IP or hostname of the MySQL server ' .
                'to the given regular expression.'
            ),
            'AllowThirdPartyFraming_desc' => __(
                'Enabling this allows a page located on a different domain to call phpMyAdmin '
                . 'inside a frame, and is a potential [strong]security hole[/strong] allowing '
                . 'cross-frame scripting (XSS) attacks.'
            ),
            'blowfish_secret_desc' => __(
                'Secret passphrase used for encrypting cookies in [kbd]cookie[/kbd] authentication.'
            ),
            'BZipDump_desc' => __('Enable bzip2 compression for import operations.'),
            'CaptchaApi_desc' => __('Enter the URL for your reCAPTCHA v2 compatible API.'),
            'CaptchaCsp_desc' => __('Enter the Content-Security-Policy snippet for your reCAPTCHA v2 compatible API.'),
            'CaptchaRequestParam_desc' => __('Enter the request parameter used by your reCAPTCHA v2 compatible API.'),
            'CaptchaResponseParam_desc' => __('Enter the response parameter used by your reCAPTCHA v2 compatible API.'),
            'CaptchaLoginPublicKey_desc' => __('Enter your public key for the reCAPTCHA service on your domain.'),
            'CaptchaLoginPrivateKey_desc' => __('Enter your private key for your domain reCAPTCHA service.'),
            'CaptchaSiteVerifyURL_desc' => __('Enter your siteverify URL for your reCAPTCHA service.'),
            'CharEditing_desc' => __(
                'Defines which type of editing controls should be used for CHAR and VARCHAR '
                . 'columns; [kbd]input[/kbd] - allows limiting of input length, '
                . '[kbd]textarea[/kbd] - allows newlines in columns.'
            ),
            'CodemirrorEnable_desc' => __(
                'Use user-friendly editor for editing SQL queries '
                . '(CodeMirror) with syntax highlighting and '
                . 'line numbers.'
            ),
            'LintEnable_desc' => __(
                'Find any errors in the query before executing it. Requires CodeMirror to be enabled.'
            ),
            'MinSizeForInputField_desc' => __(
                'Defines the minimum size for input fields generated for CHAR and VARCHAR columns.'
            ),
            'MaxSizeForInputField_desc' => __(
                'Defines the maximum size for input fields generated for CHAR and VARCHAR columns.'
            ),
            'CompressOnFly_desc' => __(
                'Compress gzip exports on the fly without the need for much memory; if '
                . 'you encounter problems with created gzip files disable this feature.'
            ),
            'Confirm_desc' => __(
                'Whether a warning ("Are your really sure…") should be displayed when you\'re about to lose data.'
            ),
            'EnableAutocompleteForTablesAndColumns_desc' => __(
                'Autocomplete of the table and column names in the SQL queries.'
            ),
            'DefaultTransformations_Hex_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_Substring_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_Bool2Text_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_External_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_PreApPend_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_DateFormat_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_Inline_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_TextImageLink_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DefaultTransformations_TextLink_desc' => __(
                'Values for options list for default transformations. These will be overwritten if'
                . ' transformation is filled in at table structure page.'
            ),
            'DisableMultiTableMaintenance_desc' => __(
                'Disable the table maintenance mass operations, like optimizing or repairing '
                . 'the selected tables of a database.'
            ),
            'ExecTimeLimit_desc' => __(
                'Set the number of seconds a script is allowed to run ([kbd]0[/kbd] for no limit).'
            ),
            'Export_sql_view_current_user' => __('Exclude definition of current user'),
            'ForeignKeyDropdownOrder_desc' => __(
                'Sort order for items in a foreign-key dropdown box; [kbd]content[/kbd] is '
                . 'the referenced data, [kbd]id[/kbd] is the key value.'
            ),
            'Form_Page_titles_desc' => __(
                'Specify browser\'s title bar text. Refer to '
                . '[doc@faq6-27]documentation[/doc] for magic strings that can be used '
                . 'to get special values.'
            ),
            'Form_Security_desc' => __(
                'Please note that phpMyAdmin is just a user interface and its features do not limit MySQL.'
            ),
            'Form_Server_config_desc' => __(
                'Advanced server configuration, do not change these options unless you know what they are for.'
            ),
            'Form_Server_pmadb_desc' => __(
                'Configure phpMyAdmin configuration storage to gain access to additional '
                . 'features, see [doc@linked-tables]phpMyAdmin configuration storage[/doc] in '
                . 'documentation.'
            ),
            'Form_Server_tracking_desc' => __(
                'Tracking of changes made in database. Requires the phpMyAdmin configuration storage.'
            ),
            'Form_DisplayRelationalSchema_desc' => '',
            'Form_Browse_desc' => __('Customize browse mode.'),
            'Form_CodeGen_desc' => __('Customize default options.'),
            'Form_Csv_desc' => __('Customize default options.'),
            'Form_Developer_desc' => __('Settings for phpMyAdmin developers.'),
            'Form_Edit_desc' => __('Customize edit mode.'),
            'Form_Export_defaults_desc' => __('Customize default export options.'),
            'Form_General_desc' => __('Set some commonly used options.'),
            'Form_Import_defaults_desc' => __('Customize default common import options.'),
            'Form_Import_export_desc' => __('Set import and export directories and compression options.'),
            'Form_Latex_desc' => __('Customize default options.'),
            'Form_Navi_databases_desc' => __('Databases display options.'),
            'Form_Navi_panel_desc' => __('Customize appearance of the navigation panel.'),
            'Form_Navi_tree_desc' => __('Customize the navigation tree.'),
            'Form_Navi_servers_desc' => __('Servers display options.'),
            'Form_Navi_tables_desc' => __('Tables display options.'),
            'Form_Microsoft_Office_desc' => __('Customize default options.'),
            'Form_Open_Document_desc' => __('Customize default options.'),
            'Form_Other_core_settings_desc' => __('Settings that didn\'t fit anywhere else.'),
            'Form_Server_auth_desc' => __('Authentication settings.'),
            'Form_Server_desc' => __('Enter server connection parameters.'),
            'Form_Sql_box_desc' => __('Customize links shown in SQL Query boxes.'),
            'Form_Sql_desc' => __('Customize default options.'),
            'Form_Sql_queries_desc' => __('SQL queries settings.'),
            'Form_Startup_desc' => __('Customize startup page.'),
            'Form_DbStructure_desc' => __('Choose which details to show in the database structure (list of tables).'),
            'Form_TableStructure_desc' => __('Settings for the table structure (list of columns).'),
            'Form_Tabs_desc' => __('Choose how you want tabs to work.'),
            'Form_Text_fields_desc' => __('Customize text input fields.'),
            'Form_Texy_desc' => __('Customize default options'),
            'Form_Warnings_desc' => __('Disable some of the warnings shown by phpMyAdmin.'),
            'PDFDefaultPageSize_desc' => '',
            'GZipDump_desc' => __('Enable gzip compression for import and export operations.'),
            'IgnoreMultiSubmitErrors_desc' => __(
                'If enabled, phpMyAdmin continues computing multiple-statement queries even if '
                . 'one of the queries failed.'
            ),
            'Import_allow_interrupt_desc' => __(
                'Allow interrupt of import in case script detects it is close to time limit. '
                . 'This might be a good way to import large files, however it can break '
                . 'transactions.'
            ),
            'Import_format_desc' => __(
                'Default format; be aware that this list depends on location (database, table) '
                . 'and only SQL is always available.'
            ),
            'Import_csv_replace_desc' => __('Update data when duplicate keys found on import'),
            'Import_ldi_replace_desc' => __('Update data when duplicate keys found on import'),
            'Import_skip_queries_desc' => __('Number of queries to skip from start.'),
            'LoginCookieDeleteAll_desc' => __(
                'If TRUE, logout deletes cookies for all servers; when set to FALSE, logout '
                . 'only occurs for the current server. Setting this to FALSE makes it easy to '
                . 'forget to log out from other servers when connected to multiple servers.'
            ),
            'LoginCookieRecall_desc' => __(
                'Define whether the previous login should be recalled or not in '
                . '[kbd]cookie[/kbd] authentication mode.'
            ),
            'LoginCookieStore_desc' => __(
                'Defines how long (in seconds) a login cookie should be stored in browser. '
                . 'The default of 0 means that it will be kept for the existing session only, '
                . 'and will be deleted as soon as you close the browser window. This is '
                . 'recommended for non-trusted environments.'
            ),
            'MaxCharactersInDisplayedSQL_desc' => __(
                'Maximum number of characters used when a SQL query is displayed.'
            ),
            'FirstLevelNavigationItems_desc' => __(
                'The number of items that can be displayed on each page on the first level of the navigation tree.'
            ),
            'MaxNavigationItems_desc' => __(
                'The number of items that can be displayed on each page of the navigation tree.'
            ),
            'MaxRows_desc' => __(
                'Number of rows displayed when browsing a result set. If the result set '
                . 'contains more rows, "Previous" and "Next" links will be '
                . 'shown.'
            ),
            'MemoryLimit_desc' => __(
                'The number of bytes a script is allowed to allocate, e.g. [kbd]32M[/kbd] '
                . '([kbd]-1[/kbd] for no limit and [kbd]0[/kbd] for no change).'
            ),
            'ShowDatabasesNavigationAsTree_desc' => __(
                'In the navigation panel, replaces the database tree with a selector'
            ),
            'NavigationLinkWithMainPanel_desc' => __(
                'Link with main panel by highlighting the current database or table.'
            ),
            'NavigationLogoLinkWindow_desc' => __(
                'Open the linked page in the main window ([code]main[/code]) or in a new one ([code]new[/code]).'
            ),
            'NavigationTreeDisplayItemFilterMinimum_desc' => __(
                'Defines the minimum number of items (tables, views, routines and events) to display a filter box.'
            ),
            'NavigationTreeEnableGrouping_desc' => __(
                'Group items in the navigation tree (determined by the separator defined in ' .
                'the Databases and Tables tabs above).'
            ),
            'NavigationTreeEnableExpansion_desc' => __(
                'Whether to offer the possibility of tree expansion in the navigation panel.'
            ),
            'NavigationTreeShowProcedures_desc' => __(
                'Whether to show procedures under database in the navigation tree'
            ),
            'NavigationTreeAutoexpandSingleDb_desc' => __(
                'Whether to expand single database in the navigation tree automatically.'
            ),
            'NavigationWidth_desc' => __('Set to 0 to collapse navigation panel.'),
            'NavigationDisplayLogo_desc' => __('Show logo in navigation panel.'),
            'NavigationLogoLink_desc' => __('URL where logo in the navigation panel will point to.'),
            'NavigationDisplayServers_desc' => __('Display server choice at the top of the navigation panel.'),
            'NavigationTreeDbSeparator_desc' => __('String that separates databases into different tree levels.'),
            'NavigationTreeTableSeparator_desc' => __('String that separates tables into different tree levels.'),
            'NavigationTreePointerEnable_desc' => __('Highlight server under the mouse cursor.'),
            'NavigationTreeShowTables_desc' => __('Whether to show tables under database in the navigation tree'),
            'NavigationTreeShowViews_desc' => __('Whether to show views under database in the navigation tree'),
            'NavigationTreeShowFunctions_desc' => __('Whether to show functions under database in the navigation tree'),
            'NavigationTreeShowEvents_desc' => __('Whether to show events under database in the navigation tree'),
            'Order_desc' => __(
                '[kbd]SMART[/kbd] - i.e. descending order for columns of type TIME, DATE, '
                . 'DATETIME and TIMESTAMP, ascending order otherwise.'
            ),
            'PmaNoRelation_DisableWarning_desc' => __(
                'Disable the default warning that is displayed on the database details '
                . 'Structure page if any of the required tables for the phpMyAdmin '
                . 'configuration storage could not be found.'
            ),
            'ReservedWordDisableWarning_desc' => __(
                'Disable the default warning that is displayed on the Structure page if column '
                . 'names in a table are reserved MySQL words.'
            ),
            'QueryHistoryDB_desc' => __(
                'Enable if you want DB-based query history (requires phpMyAdmin configuration '
                . 'storage). If disabled, this utilizes JS-routines to display query history '
                . '(lost by window close).'
            ),
            'Servers_SessionTimeZone_desc' => __(
                'Sets the effective timezone; possibly different than the one from your database server'
            ),
            'Servers_bookmarktable_desc' => __(
                'Leave blank for no [doc@bookmarks@]bookmark[/doc] support, suggested: [kbd]pma__bookmark[/kbd]'
            ),
            'Servers_column_info_desc' => __(
                'Leave blank for no column comments/media types, suggested: [kbd]pma__column_info[/kbd].'
            ),
            'Servers_controluser_desc' => __(
                'A special MySQL user configured with limited permissions, more information '
                . 'available on [doc@linked-tables]documentation[/doc].'
            ),
            'Servers_controlhost_desc' => __(
                'An alternate host to hold the configuration storage; leave blank to use the already defined host.'
            ),
            'Servers_controlport_desc' => __(
                'An alternate port to connect to the host that holds the configuration storage; '
                . 'leave blank to use the default port, or the already defined port, if the '
                . 'controlhost equals host.'
            ),
            'Servers_DisableIS_desc' => __(
                'More information on [a@https://github.com/phpmyadmin/phpmyadmin/issues/8970]phpMyAdmin '
                . 'issue tracker[/a] and [a@https://bugs.mysql.com/19588]MySQL Bugs[/a]'
            ),
            'Servers_history_desc' => __(
                'Leave blank for no SQL query history support, suggested: [kbd]pma__history[/kbd].'
            ),
            'Servers_MaxTableUiprefs_desc' => __(
                'Limits number of table preferences which are stored in database, the oldest '
                . 'records are automatically removed.'
            ),
            'Servers_savedsearches_desc' => __(
                'Leave blank for no QBE saved searches support, suggested: [kbd]pma__savedsearches[/kbd].'
            ),
            'Servers_export_templates_desc' => __(
                'Leave blank for no export template support, suggested: [kbd]pma__export_templates[/kbd].'
            ),
            'Servers_central_columns_desc' => __(
                'Leave blank for no central columns support, suggested: [kbd]pma__central_columns[/kbd].'
            ),
            'Servers_only_db_desc' => __(
                'You can use MySQL wildcard characters (% and _), escape them if you want to '
                . 'use their literal instances, i.e. use [kbd]\'my\_db\'[/kbd] and not '
                . '[kbd]\'my_db\'[/kbd].'
            ),
            'Servers_pdf_pages_desc' => __(
                'Leave blank for no PDF schema support, suggested: [kbd]pma__pdf_pages[/kbd].'
            ),
            'Servers_pmadb_desc' => __(
                'Database used for relations, bookmarks, and PDF features. See '
                . '[doc@linked-tables]pmadb[/doc] for complete information. '
                . 'Leave blank for no support. Suggested: [kbd]phpmyadmin[/kbd].'
            ),
            'Servers_recent_desc' => __(
                'Leave blank for no "persistent" recently used tables across sessions, '
                . 'suggested: [kbd]pma__recent[/kbd].'
            ),
            'Servers_favorite_desc' => __(
                'Leave blank for no "persistent" favorite tables across sessions, '
                . 'suggested: [kbd]pma__favorite[/kbd].'
            ),
            'Servers_relation_desc' => __(
                'Leave blank for no '
                . '[doc@relations@]relation-links[/doc] support, '
                . 'suggested: [kbd]pma__relation[/kbd].'
            ),
            'Servers_SignonSession_desc' => __(
                'See [doc@authentication-modes]authentication types[/doc] for an example.'
            ),
            'Servers_table_coords_desc' => __(
                'Leave blank for no PDF schema support, suggested: [kbd]pma__table_coords[/kbd].'
            ),
            'Servers_table_info_desc' => __(
                'Table to describe the display columns, leave blank for no support; '
                . 'suggested: [kbd]pma__table_info[/kbd].'
            ),
            'Servers_table_uiprefs_desc' => __(
                'Leave blank for no "persistent" tables\' UI preferences across sessions, '
                . 'suggested: [kbd]pma__table_uiprefs[/kbd].'
            ),
            'Servers_tracking_add_drop_database_desc' => __(
                'Whether a DROP DATABASE IF EXISTS statement will be added as first line to '
                . 'the log when creating a database.'
            ),
            'Servers_tracking_add_drop_table_desc' => __(
                'Whether a DROP TABLE IF EXISTS statement will be added as first line to the '
                . 'log when creating a table.'
            ),
            'Servers_tracking_add_drop_view_desc' => __(
                'Whether a DROP VIEW IF EXISTS statement will be added as first line to the '
                . 'log when creating a view.'
            ),
            'Servers_tracking_default_statements_desc' => __(
                'Defines the list of statements the auto-creation uses for new versions.'
            ),
            'Servers_tracking_desc' => __(
                'Leave blank for no SQL query tracking support, suggested: [kbd]pma__tracking[/kbd].'
            ),
            'Servers_tracking_version_auto_create_desc' => __(
                'Whether the tracking mechanism creates versions for tables and views automatically.'
            ),
            'Servers_userconfig_desc' => __(
                'Leave blank for no user preferences storage in database, suggested: [kbd]pma__userconfig[/kbd].'
            ),
            'Servers_users_desc' => __(
                'Both this table and the user groups table are required to enable the ' .
                'configurable menus feature; leaving either one of them blank will disable ' .
                'this feature, suggested: [kbd]pma__users[/kbd].'
            ),
            'Servers_usergroups_desc' => __(
                'Both this table and the users table are required to enable the configurable ' .
                'menus feature; leaving either one of them blank will disable this feature, ' .
                'suggested: [kbd]pma__usergroups[/kbd].'
            ),
            'Servers_navigationhiding_desc' => __(
                'Leave blank to disable the feature to hide and show navigation items, ' .
                'suggested: [kbd]pma__navigationhiding[/kbd].'
            ),
            'Servers_verbose_desc' => __(
                'A user-friendly description of this server. Leave blank to display the hostname instead.'
            ),
            'Servers_AllowDeny_order_desc' => __('Leave blank if not used.'),
            'Servers_AllowDeny_rules_desc' => __('Leave blank for defaults.'),
            'Servers_auth_http_realm_desc' => __('HTTP Basic Auth Realm name to display when doing HTTP Auth.'),
            'Servers_auth_type_desc' => __('Authentication method to use.'),
            'Servers_compress_desc' => __('Compress connection to MySQL server.'),
            'Servers_hide_db_desc' => __('Hide databases matching regular expression (PCRE).'),
            'Servers_host_desc' => __('Hostname where MySQL server is running.'),
            'Servers_password_desc' => __('Leave empty if not using config auth.'),
            'Servers_port_desc' => __('Port on which MySQL server is listening, leave empty for default.'),
            'Servers_socket_desc' => __('Socket on which MySQL server is listening, leave empty for default.'),
            'Servers_ssl_desc' => __('Enable SSL for connection to MySQL server.'),
            'Servers_user_desc' => __('Leave empty if not using config auth.'),
            'ShowChgPassword_desc' => __(
                'Please note that enabling this has no effect with [kbd]config[/kbd] ' .
                'authentication mode because the password is hard coded in the configuration ' .
                'file; this does not limit the ability to execute the same command directly.'
            ),
            'ShowDbStructureCreation_desc' => __(
                'Show or hide a column displaying the Creation timestamp for all tables.'
            ),
            'ShowDbStructureLastUpdate_desc' => __(
                'Show or hide a column displaying the Last update timestamp for all tables.'
            ),
            'ShowDbStructureLastCheck_desc' => __(
                'Show or hide a column displaying the Last check timestamp for all tables.'
            ),
            'ShowFieldTypesInDataEditView_desc' => __(
                'Defines whether or not type fields should be initially displayed in edit/insert mode.'
            ),
            'ShowPhpInfo_desc' => __(
                'Shows link to [a@https://www.php.net/manual/en/function.phpinfo.php]phpinfo()[/a] output.'
            ),
            'ShowAll_desc' => __('Whether a user should be displayed a "show all (rows)" button.'),
            'ShowDbStructureComment_desc' => __('Show or hide a column displaying the comments for all tables.'),
            'ShowDbStructureCharset_desc' => __('Show or hide a column displaying the charset for all tables.'),
            'ShowFunctionFields_desc' => __('Display the function fields in edit/insert mode.'),
            'ShowHint_desc' => __('Whether to show hint or not.'),
            'ShowSQL_desc' => __('Defines whether SQL queries generated by phpMyAdmin should be displayed.'),
            'ShowStats_desc' => __('Allow to display database and table statistics (e.g. space usage).'),
            'SkipLockedTables_desc' => __(
                'Mark used tables and make it possible to show databases with locked tables.'
            ),
            'SuhosinDisableWarning_desc' => __(
                'Disable the default warning that is displayed on the main page if Suhosin is detected.'
            ),
            'LoginCookieValidityDisableWarning_desc' => __(
                'Disable the default warning that is displayed on the main page if the value ' .
                'of the PHP setting session.gc_maxlifetime is less than the value of ' .
                '`LoginCookieValidity`.'
            ),
            'TextareaCols_desc' => __(
                'Textarea size (columns) in edit mode, this value will be emphasized for SQL query textareas (*2).'
            ),
            'TextareaRows_desc' => __(
                'Textarea size (rows) in edit mode, this value will be emphasized for SQL query textareas (*2).'
            ),
            'TrustedProxies_desc' => __(
                'Input proxies as [kbd]IP: trusted HTTP header[/kbd]. The following example ' .
                'specifies that phpMyAdmin should trust a HTTP_X_FORWARDED_FOR ' .
                '(X-Forwarded-For) header coming from the proxy 1.2.3.4:[br][kbd]1.2.3.4: ' .
                'HTTP_X_FORWARDED_FOR[/kbd].'
            ),
            'UserprefsDeveloperTab_desc' => __(
                'When disabled, users cannot set any of the options below, regardless of the checkbox on the right.'
            ),
            'ProxyUrl_desc' => __(
                'The URL of the proxy to be used when retrieving the information about the ' .
                'latest version of phpMyAdmin, or when submitting error reports. You need this ' .
                'if the server where phpMyAdmin is installed does not have direct access to ' .
                'the Internet. The format is: "hostname:portnumber".'
            ),
            'ProxyUser_desc' => __(
                'The username for authenticating with the proxy. By default, no ' .
                'authentication is performed. If a username is supplied, Basic ' .
                'Authentication will be performed. No other types of authentication are ' .
                'currently supported.'
            ),
            'ConsoleEnterExecutes_desc' => __(
                'Queries are executed by pressing Enter (instead of Ctrl+Enter). New lines ' .
                'will be inserted with Shift+Enter.'
            ),
            'ZeroConf_desc' => __(
                'Enable Zero Configuration mode which lets you set up phpMyAdmin '
                . 'configuration storage tables automatically.'
            ),
            'BrowseMarkerEnable_desc' => __('Highlight selected rows.'),
            'BrowsePointerEnable_desc' => __('Highlight row pointed by the mouse cursor.'),
            'CharTextareaCols_desc' => __('Number of columns for CHAR/VARCHAR textareas.'),
            'CharTextareaRows_desc' => __('Number of rows for CHAR/VARCHAR textareas.'),
            'DBG_sql_desc' => __('Log SQL queries and their execution time, to be displayed in the console'),
            'DefaultTabDatabase_desc' => __('Tab that is displayed when entering a database.'),
            'DefaultTabServer_desc' => __('Tab that is displayed when entering a server.'),
            'DefaultTabTable_desc' => __('Tab that is displayed when entering a table.'),
            'HideStructureActions_desc' => __('Whether the table structure actions should be hidden.'),
            'ShowColumnComments_desc' => __('Whether column comments should be shown in table structure view'),
            'DisplayServersList_desc' => __('Show server listing as a list instead of a drop down.'),
            'ForeignKeyMaxLimit_desc' => __('A dropdown will be used if fewer items are present.'),
            'DefaultForeignKeyChecks_desc' => __('Default value for foreign key checks checkbox for some queries.'),
            'enable_drag_drop_import_desc' => __('Uncheck the checkbox to disable drag and drop import'),
            'InsertRows_desc' => __('How many rows can be inserted at one time.'),
            'LimitChars_desc' => __('Maximum number of characters shown in any non-numeric column on browse view.'),
            'LoginCookieValidity_desc' => __('Define how long (in seconds) a login cookie is valid.'),
            'LongtextDoubleTextarea_desc' => __('Double size of textarea for LONGTEXT columns.'),
            'MaxDbList_desc' => __('Maximum number of databases displayed in database list.'),
            'MaxTableList_desc' => __('Maximum number of tables displayed in table list.'),
            'NumRecentTables_desc' => __('Maximum number of recently used tables; set 0 to disable.'),
            'NumFavoriteTables_desc' => __('Maximum number of favorite tables; set 0 to disable.'),
            'RowActionLinks_desc' => __('These are Edit, Copy and Delete links.'),
            'RowActionLinksWithoutUnique_desc' => __('Whether to show row links even in the absence of a unique key.'),
            'DisableShortcutKeys_desc' => __('Disable shortcut keys'),
            'NaturalOrder_desc' => __('Use natural order for sorting table and database names.'),
            'TableNavigationLinksMode_desc' => __('Use only icons, only text or both.'),
            'OBGzip_desc' => __('Use GZip output buffering for increased speed in HTTP transfers.'),
            'PersistentConnections_desc' => __('Use persistent connections to MySQL databases.'),
            'TabsMode_desc' => __('Use only icons, only text or both.'),
            'ActionLinksMode_desc' => __('Use only icons, only text or both.'),
            'ProtectBinary_desc' => __('Disallow BLOB and BINARY columns from editing.'),
            'QueryHistoryMax_desc' => __('How many queries are kept in history.'),
            'RecodingEngine_desc' => __('Select which functions will be used for character set conversion.'),
            'RememberSorting_desc' => __('When browsing tables, the sorting of each table is remembered.'),
            'TablePrimaryKeyOrder_desc' => __('Default sort order for tables with a primary key.'),
            'RepeatCells_desc' => __('Repeat the headers every X cells, [kbd]0[/kbd] deactivates this feature.'),
            'RelationalDisplay_desc' => __('For display Options'),
            'SaveDir_desc' => __('Directory where exports can be saved on server.'),
            'RetainQueryBox_desc' => __('Defines whether the query box should stay on-screen after its submission.'),
            'TitleDatabase_desc' => __('Title of browser window when a database is selected.'),
            'TitleDefault_desc' => __('Title of browser window when nothing is selected.'),
            'TitleServer_desc' => __('Title of browser window when a server is selected.'),
            'TitleTable_desc' => __('Title of browser window when a table is selected.'),
            'UploadDir_desc' => __('Directory on server where you can upload files for import.'),
            'UseDbSearch_desc' => __('Allow for searching inside the entire database.'),
            'VersionCheck_desc' => __('Enables check for latest version on main phpMyAdmin page.'),
            'ProxyPass_desc' => __('The password for authenticating with the proxy.'),
            'ZipDump_desc' => __('Enable ZIP compression for import and export operations.'),
            'SendErrorReports_desc' => __('Choose the default action when sending error reports.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function getNames(): array
    {
        /** @var array<string, string> $namesMap */
        static $namesMap = [];

        if ($namesMap !== []) {
            return $namesMap;
        }

        return $namesMap = [
            'AllowArbitraryServer_name' => __('Allow login to any MySQL server'),
            'ArbitraryServerRegexp_name' => __('Restrict login to MySQL server'),
            'AllowThirdPartyFraming_name' => __('Allow third party framing'),
            'AllowUserDropDatabase_name' => __('Show "Drop database" link to normal users'),
            'blowfish_secret_name' => __('Blowfish secret'),
            'BrowseMarkerEnable_name' => __('Row marker'),
            'BrowsePointerEnable_name' => __('Highlight pointer'),
            'BZipDump_name' => __('Bzip2'),
            'CharEditing_name' => __('CHAR columns editing'),
            'CodemirrorEnable_name' => __('Enable CodeMirror'),
            'LintEnable_name' => __('Enable linter'),
            'MinSizeForInputField_name' => __('Minimum size for input field'),
            'MaxSizeForInputField_name' => __('Maximum size for input field'),
            'CharTextareaCols_name' => __('CHAR textarea columns'),
            'CharTextareaRows_name' => __('CHAR textarea rows'),
            'CheckConfigurationPermissions_name' => __('Check config file permissions'),
            'CompressOnFly_name' => __('Compress on the fly'),
            'Confirm_name' => __('Confirm DROP queries'),
            'DBG_sql_name' => __('Debug SQL'),
            'PDFDefaultPageSize_name' => __('Paper size'),
            'DefaultTabDatabase_name' => __('Default database tab'),
            'DefaultTabServer_name' => __('Default server tab'),
            'DefaultTabTable_name' => __('Default table tab'),
            'EnableAutocompleteForTablesAndColumns_name' => __('Enable autocomplete for table and column names'),
            'ShowColumnComments_name' => __('Show column comments'),
            'HideStructureActions_name' => __('Hide table structure actions'),
            'DefaultTransformations_Hex_name' => __('Default transformations for Hex'),
            'DefaultTransformations_Substring_name' => __('Default transformations for Substring'),
            'DefaultTransformations_Bool2Text_name' => __('Default transformations for Bool2Text'),
            'DefaultTransformations_External_name' => __('Default transformations for External'),
            'DefaultTransformations_PreApPend_name' => __('Default transformations for PreApPend'),
            'DefaultTransformations_DateFormat_name' => __('Default transformations for DateFormat'),
            'DefaultTransformations_Inline_name' => __('Default transformations for Inline'),
            'DefaultTransformations_TextImageLink_name' => __('Default transformations for TextImageLink'),
            'DefaultTransformations_TextLink_name' => __('Default transformations for TextLink'),
            'DisplayServersList_name' => __('Display servers as a list'),
            'DisableMultiTableMaintenance_name' => __('Disable multi table maintenance'),
            'ExecTimeLimit_name' => __('Maximum execution time'),
            'Export_lock_tables_name' => __('Use [code]LOCK TABLES[/code] statement'),
            'Export_asfile_name' => __('Save as file'),
            'Export_charset_name' => __('Character set of the file'),
            'Export_codegen_format_name' => __('Format'),
            'Export_compression_name' => __('Compression'),
            'Export_csv_columns_name' => __('Put columns names in the first row'),
            'Export_csv_enclosed_name' => __('Columns enclosed with'),
            'Export_csv_escaped_name' => __('Columns escaped with'),
            'Export_csv_null_name' => __('Replace NULL with'),
            'Export_csv_removeCRLF_name' => __('Remove CRLF characters within columns'),
            'Export_csv_separator_name' => __('Columns terminated with'),
            'Export_csv_terminated_name' => __('Lines terminated with'),
            'Export_excel_columns_name' => __('Put columns names in the first row'),
            'Export_excel_edition_name' => __('Excel edition'),
            'Export_excel_null_name' => __('Replace NULL with'),
            'Export_excel_removeCRLF_name' => __('Remove CRLF characters within columns'),
            'Export_file_template_database_name' => __('Database name template'),
            'Export_file_template_server_name' => __('Server name template'),
            'Export_file_template_table_name' => __('Table name template'),
            'Export_format_name' => __('Format'),
            'Export_htmlword_columns_name' => __('Put columns names in the first row'),
            'Export_htmlword_null_name' => __('Replace NULL with'),
            'Export_htmlword_structure_or_data_name' => __('Dump table'),
            'Export_latex_caption_name' => __('Include table caption'),
            'Export_latex_columns_name' => __('Put columns names in the first row'),
            'Export_latex_comments_name' => __('Comments'),
            'Export_latex_data_caption_name' => __('Table caption'),
            'Export_latex_data_continued_caption_name' => __('Continued table caption'),
            'Export_latex_data_label_name' => __('Label key'),
            'Export_latex_mime_name' => __('Media type'),
            'Export_latex_null_name' => __('Replace NULL with'),
            'Export_latex_relation_name' => __('Relationships'),
            'Export_latex_structure_caption_name' => __('Table caption'),
            'Export_latex_structure_continued_caption_name' => __('Continued table caption'),
            'Export_latex_structure_label_name' => __('Label key'),
            'Export_latex_structure_or_data_name' => __('Dump table'),
            'Export_method_name' => __('Export method'),
            'Export_ods_columns_name' => __('Put columns names in the first row'),
            'Export_ods_null_name' => __('Replace NULL with'),
            'Export_odt_columns_name' => __('Put columns names in the first row'),
            'Export_odt_comments_name' => __('Comments'),
            'Export_odt_mime_name' => __('Media type'),
            'Export_odt_null_name' => __('Replace NULL with'),
            'Export_odt_relation_name' => __('Relationships'),
            'Export_odt_structure_or_data_name' => __('Dump table'),
            'Export_onserver_name' => __('Save on server'),
            'Export_onserver_overwrite_name' => __('Overwrite existing file(s)'),
            'Export_as_separate_files_name' => __('Export as separate files'),
            'Export_quick_export_onserver_name' => __('Save on server'),
            'Export_quick_export_onserver_overwrite_name' => __('Overwrite existing file(s)'),
            'Export_remember_file_template_name' => __('Remember filename template'),
            'Export_remove_definer_from_definitions_name' => __('Remove DEFINER clause from definitions'),
            'Export_sql_auto_increment_name' => __('Add AUTO_INCREMENT value'),
            'Export_sql_backquotes_name' => __('Enclose table and column names with backquotes'),
            'Export_sql_compatibility_name' => __('SQL compatibility mode'),
            'Export_sql_dates_name' => __('Creation/Update/Check dates'),
            'Export_sql_delayed_name' => __('Use delayed inserts'),
            'Export_sql_disable_fk_name' => __('Disable foreign key checks'),
            'Export_sql_views_as_tables_name' => __('Export views as tables'),
            'Export_sql_metadata_name' => __('Export related metadata from phpMyAdmin configuration storage'),
            'Export_sql_create_database_name' => sprintf(__('Add %s'), 'CREATE DATABASE / USE'),
            'Export_sql_drop_database_name' => sprintf(__('Add %s'), 'DROP DATABASE'),
            'Export_sql_drop_table_name' => sprintf(
                __('Add %s'),
                'DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER'
            ),
            'Export_sql_create_table_name' => sprintf(__('Add %s'), 'CREATE TABLE'),
            'Export_sql_create_view_name' => sprintf(__('Add %s'), 'CREATE VIEW'),
            'Export_sql_create_trigger_name' => sprintf(__('Add %s'), 'CREATE TRIGGER'),
            'Export_sql_hex_for_binary_name' => __('Use hexadecimal for BINARY & BLOB'),
            'Export_sql_if_not_exists_name' => __(
                'Add IF NOT EXISTS (less efficient as indexes will be generated during table creation)'
            ),
            'Export_sql_view_current_user' => __('Exclude definition of current user'),
            'Export_sql_or_replace_view_name' => sprintf(__('%s view'), 'OR REPLACE'),
            'Export_sql_ignore_name' => __('Use ignore inserts'),
            'Export_sql_include_comments_name' => __('Comments'),
            'Export_sql_insert_syntax_name' => __('Syntax to use when inserting data'),
            'Export_sql_max_query_size_name' => __('Maximal length of created query'),
            'Export_sql_mime_name' => __('Media type'),
            'Export_sql_procedure_function_name' => sprintf(__('Add %s'), 'CREATE PROCEDURE / FUNCTION / EVENT'),
            'Export_sql_relation_name' => __('Relationships'),
            'Export_sql_structure_or_data_name' => __('Dump table'),
            'Export_sql_type_name' => __('Export type'),
            'Export_sql_use_transaction_name' => __('Enclose export in a transaction'),
            'Export_sql_utc_time_name' => __('Export time in UTC'),
            'Export_texytext_columns_name' => __('Put columns names in the first row'),
            'Export_texytext_null_name' => __('Replace NULL with'),
            'Export_texytext_structure_or_data_name' => __('Dump table'),
            'ForeignKeyDropdownOrder_name' => __('Foreign key dropdown order'),
            'ForeignKeyMaxLimit_name' => __('Foreign key limit'),
            'DefaultForeignKeyChecks_name' => __('Foreign key checks'),
            'FirstDayOfCalendar_name' => __('First day of calendar'),
            'Form_Databases_name' => __('Databases'),
            'Form_Browse_name' => __('Browse mode'),
            'Form_CodeGen_name' => 'CodeGen',
            'Form_Csv_name' => __('CSV'),
            'Form_Developer_name' => __('Developer'),
            'Form_Edit_name' => __('Edit mode'),
            'Form_Export_defaults_name' => __('Export defaults'),
            'Form_General_name' => __('General'),
            'Form_Import_defaults_name' => __('Import defaults'),
            'Form_Import_export_name' => __('Import / export'),
            'Form_Latex_name' => __('LaTeX'),
            'Form_Navi_databases_name' => __('Databases'),
            'Form_Navi_panel_name' => __('Navigation panel'),
            'Form_Navi_tree_name' => __('Navigation tree'),
            'Form_Navi_servers_name' => __('Servers'),
            'Form_Navi_tables_name' => __('Tables'),
            'Form_Main_panel_name' => __('Main panel'),
            'Form_Microsoft_Office_name' => __('Microsoft Office'),
            'Form_Open_Document_name' => 'OpenDocument',
            'Form_Other_core_settings_name' => __('Other core settings'),
            'Form_Page_titles_name' => __('Page titles'),
            'Form_Security_name' => __('Security'),
            'Form_Server_name' => __('Basic settings'),
            'Form_Server_auth_name' => __('Authentication'),
            'Form_Server_config_name' => __('Server configuration'),
            'Form_Server_pmadb_name' => __('Configuration storage'),
            'Form_Server_tracking_name' => __('Changes tracking'),
            'Form_Sql_name' => __('SQL'),
            'Form_Sql_box_name' => __('SQL Query box'),
            'Form_Sql_queries_name' => __('SQL queries'),
            'Form_Startup_name' => __('Startup'),
            'Form_DbStructure_name' => __('Database structure'),
            'Form_TableStructure_name' => __('Table structure'),
            'Form_Tabs_name' => __('Tabs'),
            'Form_DisplayRelationalSchema_name' => __('Display relational schema'),
            'Form_Text_fields_name' => __('Text fields'),
            'Form_Texy_name' => __('Texy! text'),
            'Form_Warnings_name' => __('Warnings'),
            'Form_Console_name' => __('Console'),
            'GZipDump_name' => __('GZip'),
            'IconvExtraParams_name' => __('Extra parameters for iconv'),
            'IgnoreMultiSubmitErrors_name' => __('Ignore multiple statement errors'),
            'enable_drag_drop_import_name' => __('Enable drag and drop import'),
            'Import_allow_interrupt_name' => __('Partial import: allow interrupt'),
            'Import_charset_name' => __('Character set of the file'),
            'Import_csv_col_names_name' => __('Lines terminated with'),
            'Import_csv_enclosed_name' => __('Columns enclosed with'),
            'Import_csv_escaped_name' => __('Columns escaped with'),
            'Import_csv_ignore_name' => __('Do not abort on INSERT error'),
            'Import_csv_replace_name' => __('Add ON DUPLICATE KEY UPDATE'),
            'Import_csv_terminated_name' => __('Columns terminated with'),
            'Import_format_name' => __('Format of imported file'),
            'Import_ldi_enclosed_name' => __('Columns enclosed with'),
            'Import_ldi_escaped_name' => __('Columns escaped with'),
            'Import_ldi_ignore_name' => __('Do not abort on INSERT error'),
            'Import_ldi_local_option_name' => __('Use LOCAL keyword'),
            'Import_ldi_replace_name' => __('Add ON DUPLICATE KEY UPDATE'),
            'Import_ldi_terminated_name' => __('Columns terminated with'),
            'Import_ods_col_names_name' => __('Column names in first row'),
            'Import_ods_empty_rows_name' => __('Do not import empty rows'),
            'Import_ods_recognize_currency_name' => __('Import currencies ($5.00 to 5.00)'),
            'Import_ods_recognize_percentages_name' => __('Import percentages as proper decimals (12.00% to .12)'),
            'Import_skip_queries_name' => __('Partial import: skip queries'),
            'Import_sql_compatibility_name' => __('SQL compatibility mode'),
            'Import_sql_no_auto_value_on_zero_name' => __('Do not use AUTO_INCREMENT for zero values'),
            'Import_sql_read_as_multibytes_name' => __('Read as multibytes'),
            'InitialSlidersState_name' => __('Initial state for sliders'),
            'InsertRows_name' => __('Number of inserted rows'),
            'LimitChars_name' => __('Limit column characters'),
            'LoginCookieDeleteAll_name' => __('Delete all cookies on logout'),
            'LoginCookieRecall_name' => __('Recall user name'),
            'LoginCookieStore_name' => __('Login cookie store'),
            'LoginCookieValidity_name' => __('Login cookie validity'),
            'LongtextDoubleTextarea_name' => __('Bigger textarea for LONGTEXT'),
            'MaxCharactersInDisplayedSQL_name' => __('Maximum displayed SQL length'),
            'MaxDbList_name' => __('Maximum databases'),
            'FirstLevelNavigationItems_name' => __('Maximum items on first level'),
            'MaxNavigationItems_name' => __('Maximum items in branch'),
            'MaxRows_name' => __('Maximum number of rows to display'),
            'MaxTableList_name' => __('Maximum tables'),
            'MemoryLimit_name' => __('Memory limit'),
            'ShowDatabasesNavigationAsTree_name' => __('Show databases navigation as tree'),
            'NavigationWidth_name' => __('Navigation panel width'),
            'NavigationLinkWithMainPanel_name' => __('Link with main panel'),
            'NavigationDisplayLogo_name' => __('Display logo'),
            'NavigationLogoLink_name' => __('Logo link URL'),
            'NavigationLogoLinkWindow_name' => __('Logo link target'),
            'NavigationDisplayServers_name' => __('Display servers selection'),
            'NavigationTreeDefaultTabTable_name' => __('Target for quick access icon'),
            'NavigationTreeDefaultTabTable2_name' => __('Target for second quick access icon'),
            'NavigationTreeDisplayItemFilterMinimum_name' => __('Minimum number of items to display the filter box'),
            'NavigationTreeDisplayDbFilterMinimum_name' => __(
                'Minimum number of databases to display the database filter box'
            ),
            'NavigationTreeEnableGrouping_name' => __('Group items in the tree'),
            'NavigationTreeDbSeparator_name' => __('Database tree separator'),
            'NavigationTreeTableSeparator_name' => __('Table tree separator'),
            'NavigationTreeTableLevel_name' => __('Maximum table tree depth'),
            'NavigationTreePointerEnable_name' => __('Enable highlighting'),
            'NavigationTreeEnableExpansion_name' => __('Enable navigation tree expansion'),
            'NavigationTreeShowTables_name' => __('Show tables in tree'),
            'NavigationTreeShowViews_name' => __('Show views in tree'),
            'NavigationTreeShowFunctions_name' => __('Show functions in tree'),
            'NavigationTreeShowProcedures_name' => __('Show procedures in tree'),
            'NavigationTreeShowEvents_name' => __('Show events in tree'),
            'NavigationTreeAutoexpandSingleDb_name' => __('Expand single database'),
            'NumRecentTables_name' => __('Recently used tables'),
            'NumFavoriteTables_name' => __('Favorite tables'),
            'RowActionLinks_name' => __('Where to show the table row links'),
            'RowActionLinksWithoutUnique_name' => __('Show row links anyway'),
            'DisableShortcutKeys_name' => __('Disable shortcut keys'),
            'NaturalOrder_name' => __('Natural order'),
            'TableNavigationLinksMode_name' => __('Table navigation bar'),
            'OBGzip_name' => __('GZip output buffering'),
            'Order_name' => __('Default sorting order'),
            'PersistentConnections_name' => __('Persistent connections'),
            'PmaNoRelation_DisableWarning_name' => __('Missing phpMyAdmin configuration storage tables'),
            'ReservedWordDisableWarning_name' => __('MySQL reserved word warning'),
            'TabsMode_name' => __('How to display the menu tabs'),
            'ActionLinksMode_name' => __('How to display various action links'),
            'ProtectBinary_name' => __('Protect binary columns'),
            'QueryHistoryDB_name' => __('Permanent query history'),
            'QueryHistoryMax_name' => __('Query history length'),
            'RecodingEngine_name' => __('Recoding engine'),
            'RememberSorting_name' => __('Remember table\'s sorting'),
            'TablePrimaryKeyOrder_name' => __('Primary key default sort order'),
            'RepeatCells_name' => __('Repeat headers'),
            'GridEditing_name' => __('Grid editing: trigger action'),
            'RelationalDisplay_name' => __('Relational display'),
            'SaveCellsAtOnce_name' => __('Grid editing: save all edited cells at once'),
            'SaveDir_name' => __('Save directory'),
            'Servers_AllowDeny_order_name' => __('Host authorization order'),
            'Servers_AllowDeny_rules_name' => __('Host authorization rules'),
            'Servers_AllowNoPassword_name' => __('Allow logins without a password'),
            'Servers_AllowRoot_name' => __('Allow root login'),
            'Servers_SessionTimeZone_name' => __('Session timezone'),
            'Servers_auth_http_realm_name' => __('HTTP Realm'),
            'Servers_auth_type_name' => __('Authentication type'),
            'Servers_bookmarktable_name' => __('Bookmark table'),
            'Servers_column_info_name' => __('Column information table'),
            'Servers_compress_name' => __('Compress connection'),
            'Servers_controlpass_name' => __('Control user password'),
            'Servers_controluser_name' => __('Control user'),
            'Servers_controlhost_name' => __('Control host'),
            'Servers_controlport_name' => __('Control port'),
            'Servers_DisableIS_name' => __('Disable use of INFORMATION_SCHEMA'),
            'Servers_hide_db_name' => __('Hide databases'),
            'Servers_history_name' => __('SQL query history table'),
            'Servers_host_name' => __('Server hostname'),
            'Servers_LogoutURL_name' => __('Logout URL'),
            'Servers_MaxTableUiprefs_name' => __('Maximal number of table preferences to store'),
            'Servers_savedsearches_name' => __('QBE saved searches table'),
            'Servers_export_templates_name' => __('Export templates table'),
            'Servers_central_columns_name' => __('Central columns table'),
            'Servers_only_db_name' => __('Show only listed databases'),
            'Servers_password_name' => __('Password for config auth'),
            'Servers_pdf_pages_name' => __('PDF schema: pages table'),
            'Servers_pmadb_name' => __('Database name'),
            'Servers_port_name' => __('Server port'),
            'Servers_recent_name' => __('Recently used table'),
            'Servers_favorite_name' => __('Favorites table'),
            'Servers_relation_name' => __('Relation table'),
            'Servers_SignonSession_name' => __('Signon session name'),
            'Servers_SignonURL_name' => __('Signon URL'),
            'Servers_socket_name' => __('Server socket'),
            'Servers_ssl_name' => __('Use SSL'),
            'Servers_table_coords_name' => __('Designer and PDF schema: table coordinates'),
            'Servers_table_info_name' => __('Display columns table'),
            'Servers_table_uiprefs_name' => __('UI preferences table'),
            'Servers_tracking_add_drop_database_name' => __('Add DROP DATABASE'),
            'Servers_tracking_add_drop_table_name' => __('Add DROP TABLE'),
            'Servers_tracking_add_drop_view_name' => __('Add DROP VIEW'),
            'Servers_tracking_default_statements_name' => __('Statements to track'),
            'Servers_tracking_name' => __('SQL query tracking table'),
            'Servers_tracking_version_auto_create_name' => __('Automatically create versions'),
            'Servers_userconfig_name' => __('User preferences storage table'),
            'Servers_users_name' => __('Users table'),
            'Servers_usergroups_name' => __('User groups table'),
            'Servers_navigationhiding_name' => __('Hidden navigation items table'),
            'Servers_user_name' => __('User for config auth'),
            'Servers_verbose_name' => __('Verbose name of this server'),
            'ShowAll_name' => __('Allow to display all the rows'),
            'ShowChgPassword_name' => __('Show password change form'),
            'ShowCreateDb_name' => __('Show create database form'),
            'ShowDbStructureComment_name' => __('Show table comments'),
            'ShowDbStructureCreation_name' => __('Show creation timestamp'),
            'ShowDbStructureLastUpdate_name' => __('Show last update timestamp'),
            'ShowDbStructureLastCheck_name' => __('Show last check timestamp'),
            'ShowDbStructureCharset_name' => __('Show table charset'),
            'ShowFieldTypesInDataEditView_name' => __('Show field types'),
            'ShowFunctionFields_name' => __('Show function fields'),
            'ShowHint_name' => __('Show hint'),
            'ShowPhpInfo_name' => __('Show phpinfo() link'),
            'ShowServerInfo_name' => __('Show detailed MySQL server information'),
            'ShowSQL_name' => __('Show SQL queries'),
            'RetainQueryBox_name' => __('Retain query box'),
            'ShowStats_name' => __('Show statistics'),
            'SkipLockedTables_name' => __('Skip locked tables'),
            'SQLQuery_Edit_name' => __('Edit'),
            'SQLQuery_Explain_name' => __('Explain SQL'),
            'SQLQuery_Refresh_name' => __('Refresh'),
            'SQLQuery_ShowAsPHP_name' => __('Create PHP code'),
            'SuhosinDisableWarning_name' => __('Suhosin warning'),
            'LoginCookieValidityDisableWarning_name' => __('Login cookie validity warning'),
            'TextareaCols_name' => __('Textarea columns'),
            'TextareaRows_name' => __('Textarea rows'),
            'TitleDatabase_name' => __('Database'),
            'TitleDefault_name' => __('Default title'),
            'TitleServer_name' => __('Server'),
            'TitleTable_name' => __('Table'),
            'TrustedProxies_name' => __('List of trusted proxies for IP allow/deny'),
            'UploadDir_name' => __('Upload directory'),
            'UseDbSearch_name' => __('Use database search'),
            'UserprefsDeveloperTab_name' => __('Enable the Developer tab in settings'),
            'VersionCheck_name' => __('Version check'),
            'ProxyUrl_name' => __('Proxy URL'),
            'ProxyUser_name' => __('Proxy username'),
            'ProxyPass_name' => __('Proxy password'),
            'ZipDump_name' => __('ZIP'),
            'CaptchaApi_name' => __('URL for reCAPTCHA v2 API'),
            'CaptchaCsp_name' => __('Content-Security-Policy snippet for reCAPTCHA v2 API'),
            'CaptchaRequestParam_name' => __('Request parameter for reCAPTCHA v2 API'),
            'CaptchaResponseParam_name' => __('Response parameter for reCAPTCHA v2 API'),
            'CaptchaLoginPublicKey_name' => __('Public key for reCAPTCHA'),
            'CaptchaLoginPrivateKey_name' => __('Private key for reCAPTCHA'),
            'CaptchaSiteVerifyURL_name' => __('URL for reCAPTCHA siteverify'),
            'SendErrorReports_name' => __('Send error reports'),
            'ConsoleEnterExecutes_name' => __('Enter executes queries in console'),
            'ZeroConf_name' => __('Enable Zero Configuration mode'),
            'Console_StartHistory_name' => __('Show query history at start'),
            'Console_AlwaysExpand_name' => __('Always expand query messages'),
            'Console_CurrentQuery_name' => __('Show current browsing query'),
            'Console_EnterExecutes_name' => __('Execute queries on Enter and insert new line with Shift+Enter'),
            'Console_DarkTheme_name' => __('Switch to dark theme'),
            'Console_Height_name' => __('Console height'),
            'Console_Mode_name' => __('Console mode'),
            'Console_GroupQueries_name' => __('Group queries'),
            'Console_Order_name' => __('Order'),
            'Console_OrderBy_name' => __('Order by'),
            'DefaultConnectionCollation_name' => __('Server connection collation'),
        ];
    }

    /**
     * Return name or description for a cleaned up configuration path.
     *
     * @param string $path Path of configuration
     * @param string $type Type of message, either 'name', 'cmt' or 'desc'
     *
     * @return string|null Null if not found
     */
    public static function getString($path, $type = 'name'): ?string
    {
        $descriptions = [];

        if ($type === 'cmt') {
            $descriptions = self::getComments();
        } elseif ($type === 'desc') {
            $descriptions = self::getDescriptions();
        } elseif ($type === 'name') {
            $descriptions = self::getNames();
        }

        $key = $path . '_' . $type;

        return $descriptions[$key] ?? null;
    }
}
