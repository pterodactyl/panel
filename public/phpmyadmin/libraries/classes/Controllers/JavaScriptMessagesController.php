<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Theme;

use function __;
use function _pgettext;
use function json_encode;

/**
 * Exporting of translated messages from PHP to JavaScript.
 */
final class JavaScriptMessagesController
{
    /** @var array<string, string> */
    private $messages = [];

    public function __construct()
    {
        $this->setMessages();
    }

    public function __invoke(): void
    {
        echo 'var Messages = ' . json_encode($this->messages) . ';';
    }

    private function setMessages(): void
    {
        global $cfg, $theme;

        $ajaxClockSmallGifPath = $theme instanceof Theme ? $theme->getImgPath('ajax_clock_small.gif') : '';

        $this->messages = [
            /* For confirmations */
            'strConfirm' => __('Confirm'),
            'strDoYouReally' => __('Do you really want to execute "%s"?'),
            'strDropDatabaseStrongWarning' => __('You are about to DESTROY a complete database!'),
            'strDatabaseRenameToSameName' => __(
                'Cannot rename database to the same name. Change the name and try again'
            ),
            'strDropTableStrongWarning' => __('You are about to DESTROY a complete table!'),
            'strTruncateTableStrongWarning' => __('You are about to TRUNCATE a complete table!'),
            'strDeleteTableStrongWarning' => __('You are about to DELETE all the rows of the table!'),
            'strDeleteTrackingData' => __('Delete tracking data for this table?'),
            'strDeleteTrackingDataMultiple' => __('Delete tracking data for these tables?'),
            'strDeleteTrackingVersion' => __('Delete tracking data for this version?'),
            'strDeleteTrackingVersionMultiple' => __('Delete tracking data for these versions?'),
            'strDeletingTrackingEntry' => __('Delete entry from tracking report?'),
            'strDeletingTrackingData' => __('Deleting tracking data'),
            'strDroppingPrimaryKeyIndex' => __('Dropping Primary Key/Index'),
            'strDroppingForeignKey' => __('Dropping Foreign key.'),
            'strOperationTakesLongTime' => __('This operation could take a long time. Proceed anyway?'),
            'strDropUserGroupWarning' => __('Do you really want to delete user group "%s"?'),
            'strConfirmDeleteQBESearch' => __('Do you really want to delete the search "%s"?'),
            'strConfirmNavigation' => __('You have unsaved changes; are you sure you want to leave this page?'),
            'strConfirmRowChange' => __(
                'You are trying to reduce the number of rows, but have already entered'
                . ' data in those rows which will be lost. Do you wish to continue?'
            ),
            'strDropUserWarning' => __('Do you really want to revoke the selected user(s) ?'),
            'strDeleteCentralColumnWarning' => __('Do you really want to delete this central column?'),
            'strDropRTEitems' => __('Do you really want to delete the selected items?'),
            'strDropPartitionWarning' => __(
                'Do you really want to DROP the selected partition(s)? This will also DELETE ' .
                'the data related to the selected partition(s)!'
            ),
            'strTruncatePartitionWarning' => __('Do you really want to TRUNCATE the selected partition(s)?'),
            'strRemovePartitioningWarning' => __('Do you really want to remove partitioning?'),
            'strResetReplicaWarning' => __('Do you really want to reset the replica (RESET REPLICA)?'),
            'strChangeColumnCollation' => __(
                'This operation will attempt to convert your data to the new collation. In '
                    . 'rare cases, especially where a character doesn\'t exist in the new '
                    . 'collation, this process could cause the data to appear incorrectly under '
                    . 'the new collation; in this case we suggest you revert to the original '
                    . 'collation and refer to the tips at '
            )
                . '<a href="%s" target="garbled_data_wiki">' . __('Garbled Data') . '</a>.'
                . '<br><br>'
                . __('Are you sure you wish to change the collation and convert the data?'),

            'strChangeAllColumnCollationsWarning' => __(
                'Through this operation, MySQL attempts to map the data values between '
                    . 'collations. If the character sets are incompatible, there may be data loss '
                    . 'and this lost data may <b>NOT</b> be recoverable simply by changing back the '
                    . 'column collation(s). <b>To convert existing data, it is suggested to use the '
                    . 'column(s) editing feature (the "Change" Link) on the table structure page. '
                    . '</b>'
            )
                . '<br><br>'
                . __('Are you sure you wish to change all the column collations and convert the data?'),

            /* For modal dialog buttons */
            'strSaveAndClose' => __('Save & close'),
            'strReset' => __('Reset'),
            'strResetAll' => __('Reset all'),

            /* For indexes */
            'strFormEmpty' => __('Missing value in the form!'),
            'strRadioUnchecked' => __('Select at least one of the options!'),
            'strEnterValidNumber' => __('Please enter a valid number!'),
            'strEnterValidLength' => __('Please enter a valid length!'),
            'strAddIndex' => __('Add index'),
            'strEditIndex' => __('Edit index'),
            /* l10n: Rename a table Index */
            'strRenameIndex' => __('Rename index'),
            'strAddToIndex' => __('Add %s column(s) to index'),
            'strCreateSingleColumnIndex' => __('Create single-column index'),
            'strCreateCompositeIndex' => __('Create composite index'),
            'strCompositeWith' => __('Composite with:'),
            'strMissingColumn' => __('Please select column(s) for the index.'),

            /* For Preview SQL*/
            'strPreviewSQL' => __('Preview SQL'),

            /* For Simulate DML*/
            'strSimulateDML' => __('Simulate query'),
            'strMatchedRows' => __('Matched rows:'),
            'strSQLQuery' => __('SQL query:'),

            /* Charts */
            /* l10n: Default label for the y-Axis of Charts */
            'strYValues' => __('Y values'),

            /* Database multi-table query */
            'strEmptyQuery' => __('Please enter the SQL query first.'),

            /* For server/privileges.js */
            'strHostEmpty' => __('The host name is empty!'),
            'strUserEmpty' => __('The user name is empty!'),
            'strPasswordEmpty' => __('The password is empty!'),
            'strPasswordNotSame' => __('The passwords aren\'t the same!'),
            'strRemovingSelectedUsers' => __('Removing Selected Users'),
            'strClose' => __('Close'),
            'strLock' => _pgettext('Lock the account.', 'Lock'),
            'strUnlock' => _pgettext('Unlock the account.', 'Unlock'),
            'strLockAccount' => __('Lock this account.'),
            'strUnlockAccount' => __('Unlock this account.'),

            /* For export.js */
            'strTemplateCreated' => __('Template was created.'),
            'strTemplateLoaded' => __('Template was loaded.'),
            'strTemplateUpdated' => __('Template was updated.'),
            'strTemplateDeleted' => __('Template was deleted.'),

            /* l10n: Other, small valued, queries */
            'strOther' => __('Other'),
            /* l10n: Thousands separator */
            'strThousandsSeparator' => __(','),
            /* l10n: Decimal separator */
            'strDecimalSeparator' => __('.'),

            'strChartConnectionsTitle' => __('Connections / Processes'),

            /* server status monitor */
            'strIncompatibleMonitorConfig' => __('Local monitor configuration incompatible!'),
            'strIncompatibleMonitorConfigDescription' => __(
                'The chart arrangement configuration in your browsers local storage is not '
                . 'compatible anymore to the newer version of the monitor dialog. It is very '
                . 'likely that your current configuration will not work anymore. Please reset '
                . 'your configuration to default in the <i>Settings</i> menu.'
            ),

            'strQueryCacheEfficiency' => __('Query cache efficiency'),
            'strQueryCacheUsage' => __('Query cache usage'),
            'strQueryCacheUsed' => __('Query cache used'),

            'strSystemCPUUsage' => __('System CPU usage'),
            'strSystemMemory' => __('System memory'),
            'strSystemSwap' => __('System swap'),

            'strAverageLoad' => __('Average load'),
            'strTotalMemory' => __('Total memory'),
            'strCachedMemory' => __('Cached memory'),
            'strBufferedMemory' => __('Buffered memory'),
            'strFreeMemory' => __('Free memory'),
            'strUsedMemory' => __('Used memory'),

            'strTotalSwap' => __('Total swap'),
            'strCachedSwap' => __('Cached swap'),
            'strUsedSwap' => __('Used swap'),
            'strFreeSwap' => __('Free swap'),

            'strBytesSent' => __('Bytes sent'),
            'strBytesReceived' => __('Bytes received'),
            'strConnections' => __('Connections'),
            'strProcesses' => __('Processes'),

            /* summary row */
            'strB' => __('B'),
            'strKiB' => __('KiB'),
            'strMiB' => __('MiB'),
            'strGiB' => __('GiB'),
            'strTiB' => __('TiB'),
            'strPiB' => __('PiB'),
            'strEiB' => __('EiB'),
            'strNTables' => __('%d table(s)'),

            /* l10n: Questions is the name of a MySQL Status variable */
            'strQuestions' => __('Questions'),
            'strTraffic' => __('Traffic'),
            'strSettings' => __('Settings'),
            'strAddChart' => __('Add chart to grid'),
            'strAddOneSeriesWarning' => __('Please add at least one variable to the series!'),
            'strNone' => __('None'),
            /* l10n: SQL Query on modal to show exported query */
            'strQuery' => __('SQL Query'),
            'strResumeMonitor' => __('Resume monitor'),
            'strPauseMonitor' => __('Pause monitor'),
            'strStartRefresh' => __('Start auto refresh'),
            'strStopRefresh' => __('Stop auto refresh'),
            /* Monitor: Instructions Dialog */
            'strBothLogOn' => __('general_log and slow_query_log are enabled.'),
            'strGenLogOn' => __('general_log is enabled.'),
            'strSlowLogOn' => __('slow_query_log is enabled.'),
            'strBothLogOff' => __('slow_query_log and general_log are disabled.'),
            'strLogOutNotTable' => __('log_output is not set to TABLE.'),
            'strLogOutIsTable' => __('log_output is set to TABLE.'),
            'strSmallerLongQueryTimeAdvice' => __(
                'slow_query_log is enabled, but the server logs only queries that take longer '
                . 'than %d seconds. It is advisable to set this long_query_time 0-2 seconds, '
                . 'depending on your system.'
            ),
            'strLongQueryTimeSet' => __('long_query_time is set to %d second(s).'),
            'strSettingsAppliedGlobal' => __(
                'Following settings will be applied globally and reset to default on server restart:'
            ),
            /* l10n: %s is FILE or TABLE */
            'strSetLogOutput' => __('Set log_output to %s'),
            /* l10n: Enable in this context means setting a status variable to ON */
            'strEnableVar' => __('Enable %s'),
            /* l10n: Disable in this context means setting a status variable to OFF */
            'strDisableVar' => __('Disable %s'),
            /* l10n: %d seconds */
            'setSetLongQueryTime' => __('Set long_query_time to %d seconds.'),
            'strNoSuperUser' => __(
                'You can\'t change these variables. Please log in as root or contact your database administrator.'
            ),
            'strChangeSettings' => __('Change settings'),
            'strCurrentSettings' => __('Current settings'),

            'strChartTitle' => __('Chart title'),
            /* l10n: As in differential values */
            'strDifferential' => __('Differential'),
            'strDividedBy' => __('Divided by %s'),
            'strUnit' => __('Unit'),

            'strFromSlowLog' => __('From slow log'),
            'strFromGeneralLog' => __('From general log'),
            'strServerLogError' => __('The database name is not known for this query in the server\'s logs.'),
            'strAnalysingLogsTitle' => __('Analysing logs'),
            'strAnalysingLogs' => __('Analysing & loading logs. This may take a while.'),
            'strCancelRequest' => __('Cancel request'),
            'strCountColumnExplanation' => __(
                'This column shows the amount of identical queries that are grouped together. '
                . 'However only the SQL query itself has been used as a grouping criteria, so '
                . 'the other attributes of queries, such as start time, may differ.'
            ),
            'strMoreCountColumnExplanation' => __(
                'Since grouping of INSERTs queries has been selected, INSERT queries into the '
                . 'same table are also being grouped together, disregarding of the inserted '
                . 'data.'
            ),
            'strLogDataLoaded' => __('Log data loaded. Queries executed in this time span:'),

            'strJumpToTable' => __('Jump to Log table'),
            'strNoDataFoundTitle' => __('No data found'),
            'strNoDataFound' => __('Log analysed, but no data found in this time span.'),

            'strAnalyzing' => __('Analyzing…'),
            'strExplainOutput' => __('Explain output'),
            'strStatus' => __('Status'),
            'strTime' => __('Time'),
            'strTotalTime' => __('Total time:'),
            'strProfilingResults' => __('Profiling results'),
            'strTable' => _pgettext('Display format', 'Table'),
            'strChart' => __('Chart'),

            'strAliasDatabase' => _pgettext('Alias', 'Database'),
            'strAliasTable' => _pgettext('Alias', 'Table'),
            'strAliasColumn' => _pgettext('Alias', 'Column'),

            /* l10n: A collection of available filters */
            'strFiltersForLogTable' => __('Log table filter options'),
            /* l10n: Filter as in "Start Filtering" */
            'strFilter' => __('Filter'),
            'strFilterByWordRegexp' => __('Filter queries by word/regexp:'),
            'strIgnoreWhereAndGroup' => __('Group queries, ignoring variable data in WHERE clauses'),
            'strSumRows' => __('Sum of grouped rows:'),
            'strTotal' => __('Total:'),

            'strLoadingLogs' => __('Loading logs'),
            'strRefreshFailed' => __('Monitor refresh failed'),
            'strInvalidResponseExplanation' => __(
                'While requesting new chart data the server returned an invalid response. This '
                . 'is most likely because your session expired. Reloading the page and '
                . 'reentering your credentials should help.'
            ),
            'strReloadPage' => __('Reload page'),

            'strAffectedRows' => __('Affected rows:'),

            'strFailedParsingConfig' => __('Failed parsing config file. It doesn\'t seem to be valid JSON code.'),
            'strFailedBuildingGrid' => __(
                'Failed building chart grid with imported config. Resetting to default config…'
            ),
            'strImport' => __('Import'),
            'strImportDialogTitle' => __('Import monitor configuration'),
            'strImportDialogMessage' => __('Please select the file you want to import:'),
            'strTableNameDialogMessage' => __('Please enter a valid table name.'),
            'strDBNameDialogMessage' => __('Please enter a valid database name.'),
            'strNoImportFile' => __('No files available on server for import!'),

            'strAnalyzeQuery' => __('Analyse query'),

            /* For query editor */
            'strFormatting' => __('Formatting SQL…'),
            'strNoParam' => __('No parameters found!'),

            /* For inline query editing */
            'strGo' => __('Go'),
            'strCancel' => __('Cancel'),

            /* For page-related settings */
            'strPageSettings' => __('Page-related settings'),
            'strApply' => __('Apply'),

            /* For Ajax Notifications */
            'strLoading' => __('Loading…'),
            'strAbortedRequest' => __('Request aborted!!'),
            'strProcessingRequest' => __('Processing request'),
            'strRequestFailed' => __('Request failed!!'),
            'strErrorProcessingRequest' => __('Error in processing request'),
            'strErrorCode' => __('Error code: %s'),
            'strErrorText' => __('Error text: %s'),
            'strErrorConnection' => __(
                'It seems that the connection to server has been lost. Please check your ' .
                'network connectivity and server status.'
            ),
            'strNoDatabasesSelected' => __('No databases selected.'),
            'strNoTableSelected' => __('No table selected.'),
            'strNoAccountSelected' => __('No accounts selected.'),
            'strDroppingColumn' => __('Dropping column'),
            'strAddingPrimaryKey' => __('Adding primary key'),
            'strOK' => __('OK'),
            'strDismiss' => __('Click to dismiss this notification'),

            /* For database/operations.js */
            'strRenamingDatabases' => __('Renaming databases'),
            'strCopyingDatabase' => __('Copying database'),
            'strChangingCharset' => __('Changing charset'),
            'strNo' => __('No'),

            /* For Foreign key checks */
            'strForeignKeyCheck' => __('Enable foreign key checks'),

            /* For database/structure.js */
            'strErrorRealRowCount' => __('Failed to get real row count.'),

            /* For database/search.js */
            'strSearching' => __('Searching'),
            'strHideSearchResults' => __('Hide search results'),
            'strShowSearchResults' => __('Show search results'),
            'strBrowsing' => __('Browsing'),
            'strDeleting' => __('Deleting'),
            'strConfirmDeleteResults' => __('Delete the matches for the %s table?'),

            /* For rte.js */
            'MissingReturn' => __('The definition of a stored function must contain a RETURN statement!'),
            'strExport' => __('Export'),
            'NoExportable' => __('No routine is exportable. Required privileges may be lacking.'),

            /* For ENUM/SET editor*/
            'enum_columnVals' => __('Values for column %s'),
            'enum_newColumnVals' => __('Values for a new column'),
            'enum_hint' => __('Enter each value in a separate field.'),
            'enum_addValue' => __('Add %d value(s)'),

            /* For import.js */
            'strImportCSV' => __('Note: If the file contains multiple tables, they will be combined into one.'),

            /* For sql.js */
            'strHideQueryBox' => __('Hide query box'),
            'strShowQueryBox' => __('Show query box'),
            'strEdit' => __('Edit'),
            'strDelete' => __('Delete'),
            'strNotValidRowNumber' => __('%d is not valid row number.'),
            'strBrowseForeignValues' => __('Browse foreign values'),
            'strNoAutoSavedQuery' => __('No previously auto-saved query is available. Loading default query.'),
            'strPreviousSaveQuery' => __(
                'You have a previously saved query. Click Get auto-saved query to load the query.'
            ),
            'strBookmarkVariable' => __('Variable %d:'),

            /* For Central list of columns */
            'pickColumn' => __('Pick'),
            'pickColumnTitle' => __('Column selector'),
            'searchList' => __('Search this list'),
            'strEmptyCentralList' => __(
                'No columns in the central list. Make sure the Central columns list for '
                . 'database %s has columns that are not present in the current table.'
            ),
            'seeMore' => __('See more'),

            /* For normalization */
            'strAddPrimaryKey' => __('Add primary key'),
            'strPrimaryKeyAdded' => __('Primary key added.'),
            'strToNextStep' => __('Taking you to next step…'),
            'strFinishMsg' => __("The first step of normalization is complete for table '%s'."),
            'strEndStep' => __('End of step'),
            'str2NFNormalization' => __('Second step of normalization (2NF)'),
            'strDone' => __('Done'),
            'strConfirmPd' => __('Confirm partial dependencies'),
            'strSelectedPd' => __('Selected partial dependencies are as follows:'),
            'strPdHintNote' => __(
                'Note: a, b -> d,f implies values of columns a and b combined together can '
                . 'determine values of column d and column f.'
            ),
            'strNoPdSelected' => __('No partial dependencies selected!'),
            'strBack' => __('Back'),
            'strShowPossiblePd' => __('Show me the possible partial dependencies based on data in the table'),
            'strHidePd' => __('Hide partial dependencies list'),
            'strWaitForPd' => __(
                'Sit tight! It may take few seconds depending on data size and column count of the table.'
            ),
            'strStep' => __('Step'),
            'strMoveRepeatingGroup' => '<ol><b>' . __('The following actions will be performed:') . '</b>'
                . '<li>' . __('DROP columns %s from the table %s') . '</li>'
                . '<li>' . __('Create the following table') . '</li>',
            'strNewTablePlaceholder' => 'Enter new table name',
            'strNewColumnPlaceholder' => 'Enter column name',
            'str3NFNormalization' => __('Third step of normalization (3NF)'),
            'strConfirmTd' => __('Confirm transitive dependencies'),
            'strSelectedTd' => __('Selected dependencies are as follows:'),
            'strNoTdSelected' => __('No dependencies selected!'),

            /* For server/variables.js */
            'strSave' => __('Save'),

            /* For table/select.js */
            'strHideSearchCriteria' => __('Hide search criteria'),
            'strShowSearchCriteria' => __('Show search criteria'),
            'strColumnMax' => __('Column maximum:'),
            'strColumnMin' => __('Column minimum:'),

            /* For table/find_replace.js */
            'strHideFindNReplaceCriteria' => __('Hide find and replace criteria'),
            'strShowFindNReplaceCriteria' => __('Show find and replace criteria'),

            /* For table/zoom_plot_jqplot.js */
            'strDisplayHelp' => '<ul><li>'
                . __('Each point represents a data row.')
                . '</li><li>'
                . __('Hovering over a point will show its label.')
                . '</li><li>'
                . __('To zoom in, select a section of the plot with the mouse.')
                . '</li><li>'
                . __('Click reset zoom button to come back to original state.')
                . '</li><li>'
                . __('Click a data point to view and possibly edit the data row.')
                . '</li><li>'
                . __('The plot can be resized by dragging it along the bottom right corner.')
                . '</li></ul>',
            'strHelpTitle' => 'Zoom search instructions',
            'strInputNull' => '<strong>' . __('Select two columns') . '</strong>',
            'strSameInputs' => '<strong>'
                . __('Select two different columns')
                . '</strong>',
            'strDataPointContent' => __('Data point content'),

            /* For table/change.js */
            'strIgnore' => __('Ignore'),
            'strCopy' => __('Copy'),
            'strX' => __('X'),
            'strY' => __('Y'),
            'strPoint' => __('Point'),
            'strPointN' => __('Point %d'),
            'strLineString' => __('Linestring'),
            'strPolygon' => __('Polygon'),
            'strGeometry' => __('Geometry'),
            'strInnerRing' => __('Inner ring'),
            'strOuterRing' => __('Outer ring'),
            'strAddPoint' => __('Add a point'),
            'strAddInnerRing' => __('Add an inner ring'),
            'strYes' => __('Yes'),
            'strCopyEncryptionKey' => __('Do you want to copy encryption key?'),
            'strEncryptionKey' => __('Encryption key'),
            /* l10n: Tip for HEX conversion of Integers */
            'HexConversionInfo' => __(
                'The HEX function will treat the integer as a string while calculating the hexadecimal value'
            ),

            /* For Tip to be shown on Time field */
            'strMysqlAllowedValuesTipTime' => __(
                'MySQL accepts additional values not selectable by the slider;'
                . ' key in those values directly if desired'
            ),

            /* For Tip to be shown on Date field */
            'strMysqlAllowedValuesTipDate' => __(
                'MySQL accepts additional values not selectable by the datepicker;'
                . ' key in those values directly if desired'
            ),

            /* For Lock symbol Tooltip */
            'strLockToolTip' => __(
                'Indicates that you have made changes to this page;'
                . ' you will be prompted for confirmation before abandoning changes'
            ),

            /* Designer (js/designer/move.js) */
            'strSelectReferencedKey' => __('Select referenced key'),
            'strSelectForeignKey' => __('Select Foreign Key'),
            'strPleaseSelectPrimaryOrUniqueKey' => __('Please select the primary key or a unique key!'),
            'strChangeDisplay' => __('Choose column to display'),
            'strLeavingDesigner' => __(
                'You haven\'t saved the changes in the layout. They will be lost if you'
                . ' don\'t save them. Do you want to continue?'
            ),
            'strQueryEmpty' => __('value/subQuery is empty'),
            'strAddTables' => __('Add tables from other databases'),
            'strPageName' => __('Page name'),
            'strSavePage' => __('Save page'),
            'strSavePageAs' => __('Save page as'),
            'strOpenPage' => __('Open page'),
            'strDeletePage' => __('Delete page'),
            /* l10n: When the user opens a page saved in the Designer */
            'strSavedPageTableMissing' => __('Some tables saved in this page might have been renamed or deleted.'),
            'strUntitled' => __('Untitled'),
            'strSelectPage' => __('Please select a page to continue'),
            'strEnterValidPageName' => __('Please enter a valid page name'),
            'strLeavingPage' => __('Do you want to save the changes to the current page?'),
            'strSuccessfulPageDelete' => __('Successfully deleted the page'),
            'strExportRelationalSchema' => __('Export relational schema'),
            'strModificationSaved' => __('Modifications have been saved'),

            /* Visual query builder (js/designer/move.js) */
            'strObjectsCreated' => __('%d object(s) created.'),
            'strColumnName' => __('Column name'),
            'strSubmit' => __('Submit'),

            /* For makegrid.js (column reordering, show/hide column, grid editing) */
            'strCellEditHint' => __('Press escape to cancel editing.<br>- Shift+Enter for a newline.'),
            'strSaveCellWarning' => __(
                'You have edited some data and they have not been saved. Are you sure you want '
                . 'to leave this page before saving the data?'
            ),
            'strColOrderHint' => __('Drag to reorder.'),
            'strSortHint' => __('Click to sort results by this column.'),
            'strMultiSortHint' => __(
                'Shift+Click to add this column to ORDER BY clause or to toggle ASC/DESC.'
                . '<br>- Ctrl+Click or Alt+Click (Mac: Shift+Option+Click) to remove column '
                . 'from ORDER BY clause'
            ),
            'strColMarkHint' => __('Click to mark/unmark.'),
            'strColNameCopyHint' => __('Double-click to copy column name.'),
            'strColVisibHint' => __('Click the drop-down arrow<br>to toggle column\'s visibility.'),
            'strShowAllCol' => __('Show all'),
            'strAlertNonUnique' => __(
                'This table does not contain a unique column. Features related to the grid '
                . 'edit, checkbox, Edit, Copy and Delete links may not work after saving.'
            ),
            'strEnterValidHex' => __('Please enter a valid hexadecimal string. Valid characters are 0-9, A-F.'),
            'strShowAllRowsWarning' => __(
                'Do you really want to see all of the rows? For a big table this could crash the browser.'
            ),
            'strOriginalLength' => __('Original length'),

            /* Drag & Drop sql import messages */
            'dropImportMessageCancel' => __('cancel'),
            'dropImportMessageAborted' => __('Aborted'),
            'dropImportMessageFailed' => __('Failed'),
            'dropImportMessageSuccess' => __('Success'),
            'dropImportImportResultHeader' => __('Import status'),
            'dropImportDropFiles' => __('Drop files here'),
            'dropImportSelectDB' => __('Select database first'),

            // this approach does not work when the parameter is changed via user prefs
            'strGridEditFeatureHint' => $cfg['GridEditing'] === 'double-click'
                ? __('You can also edit most values<br>by double-clicking directly on them.')
                : ($cfg['GridEditing'] === 'click'
                    ? __('You can also edit most values<br>by clicking directly on them.')
                    : ''),

            'strGoToLink' => __('Go to link:'),

            /* password generation */
            'strGeneratePassword' => __('Generate password'),
            'strGenerate' => __('Generate'),
            'strChangePassword' => __('Change password'),

            /* navigation tabs */
            'strMore' => __('More'),

            /* navigation panel */
            'strShowPanel' => __('Show panel'),
            'strHidePanel' => __('Hide panel'),
            'linkWithMain' => __('Link with main panel'),
            'unlinkWithMain' => __('Unlink from main panel'),

            /* update */
            'strNewerVersion' => __(
                'A newer version of phpMyAdmin is available and you should consider upgrading. '
                . 'The newest version is %s, released on %s.'
            ),
            /* l10n: Latest available phpMyAdmin version */
            'strLatestAvailable' => __(', latest stable version:'),
            'strUpToDate' => __('up to date'),

            /* Error Reporting */
            'strErrorOccurred' => __('A fatal JavaScript error has occurred. Would you like to send an error report?'),
            'strChangeReportSettings' => __('Change report settings'),
            'strShowReportDetails' => __('Show report details'),
            'strTimeOutError' => __('Your export is incomplete, due to a low execution time limit at the PHP level!'),

            'strTooManyInputs' => __(
                'Warning: a form on this page has more than %d fields. On submission, '
                . "some of the fields might be ignored, due to PHP's "
                . 'max_input_vars configuration.'
            ),

            'phpErrorsFound' => '<div class="alert alert-danger" role="alert">'
                . __('Some errors have been detected on the server!')
                . '<br>'
                . __('Please look at the bottom of this window.')
                . '<div>'
                . '<input id="pma_ignore_errors_popup" type="submit" value="'
                . __('Ignore')
                . '" class="btn btn-secondary float-end message_errors_found">'
                . '<input id="pma_ignore_all_errors_popup" type="submit" value="'
                . __('Ignore All')
                . '" class="btn btn-secondary float-end message_errors_found">'
                . '</div></div>',

            'phpErrorsBeingSubmitted' => '<div class="alert alert-danger" role="alert">'
                . __('Some errors have been detected on the server!')
                . '<br>'
                . __('As per your settings, they are being submitted currently, please be patient.')
                . '<br>'
                . '<img src="'
                . $ajaxClockSmallGifPath
                . '" width="16" height="16" alt="ajax clock">'
                . '</div>',
            'strCopyColumnSuccess' => __('Column name successfully copied to clipboard!'),
            'strCopyColumnFailure' => __('Column name copying to clipboard failed!'),
            'strCopyQueryButtonSuccess' => __('Successfully copied!'),
            'strCopyQueryButtonFailure' => __('Copying failed!'),

            // For console
            'strConsoleRequeryConfirm' => __('Execute this query again?'),
            'strConsoleDeleteBookmarkConfirm' => __('Do you really want to delete this bookmark?'),
            'strConsoleDebugError' => __('Some error occurred while getting SQL debug info.'),
            'strConsoleDebugSummary' => __('%s queries executed %s times in %s seconds.'),
            'strConsoleDebugArgsSummary' => __('%s argument(s) passed'),
            'strConsoleDebugShowArgs' => __('Show arguments'),
            'strConsoleDebugHideArgs' => __('Hide arguments'),
            'strConsoleDebugTimeTaken' => __('Time taken:'),
            'strNoLocalStorage' => __(
                'There was a problem accessing your browser storage, some features may not'
                . ' work properly for you. It is likely that the browser doesn\'t support storage'
                . ' or the quota limit has been reached. In Firefox, corrupted storage can also'
                . ' cause such a problem, clearing your "Offline Website Data" might help. In Safari,'
                . ' such problem is commonly caused by "Private Mode Browsing".'
            ),
            // For modals in /database/structure
            'strCopyTablesTo' => __('Copy tables to'),
            'strAddPrefix' => __('Add table prefix'),
            'strReplacePrefix' => __('Replace table with prefix'),
            'strCopyPrefix' => __('Copy table with prefix'),

            /* For password strength simulation */
            'strExtrWeak' => __('Extremely weak'),
            'strVeryWeak' => __('Very weak'),
            'strWeak' => __('Weak'),
            'strGood' => __('Good'),
            'strStrong' => __('Strong'),

            /* U2F errors */
            // l10n: error code 5 (from U2F API)
            'strU2FTimeout' => _pgettext('U2F error', 'Timed out waiting for security key activation.'),
            // l10n: error code 2 (from U2F API)
            'strU2FBadRequest' => _pgettext('U2F error', 'Invalid request sent to security key.'),
            // l10n: unknown error code (from U2F API)
            'strU2FUnknown' => _pgettext('U2F error', 'Unknown security key error.'),
            // l10n: error code 3 (from U2F API)
            'strU2FInvalidClient' => _pgettext('U2F error', 'Client does not support security key.'),
            // l10n: error code 4 (from U2F API) on register
            'strU2FErrorRegister' => _pgettext('U2F error', 'Failed security key activation.'),
            // l10n: error code 4 (from U2F API) on authanticate
            'strU2FErrorAuthenticate' => _pgettext('U2F error', 'Invalid security key.'),

            'webAuthnNotSupported' => __(
                'WebAuthn is not available. Please use a supported browser in a secure context (HTTPS).'
            ),

            /* Designer */
            'strIndexedDBNotWorking' => __(
                'You can not open, save or delete your page layout, as IndexedDB is not working'
                . ' in your browser and your phpMyAdmin configuration storage is not configured for this.'
            ),
            'strTableAlreadyExists' => _pgettext(
                'The table already exists in the designer and can not be added once more.',
                'Table %s already exists!'
            ),
            'strHide' => __('Hide'),
            'strShow' => __('Show'),
            'strStructure' => __('Structure'),
        ];
    }
}
