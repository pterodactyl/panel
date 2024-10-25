<?php
/**
 * Form management class, displays and processes forms
 *
 * Explanation of used terms:
 * o work_path - original field path, eg. Servers/4/verbose
 * o system_path - work_path modified so that it points to the first server,
 *                 eg. Servers/1/verbose
 * o translated_path - work_path modified for HTML field name, a path with
 *                     slashes changed to hyphens, eg. Servers-4-verbose
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;

use function __;
use function array_flip;
use function array_keys;
use function array_search;
use function count;
use function explode;
use function function_exists;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_numeric;
use function mb_substr;
use function preg_match;
use function settype;
use function sprintf;
use function str_replace;
use function trigger_error;
use function trim;

use const E_USER_WARNING;

/**
 * Form management class, displays and processes forms
 */
class FormDisplay
{
    /**
     * ConfigFile instance
     *
     * @var ConfigFile
     */
    private $configFile;

    /**
     * Form list
     *
     * @var Form[]
     */
    private $forms = [];

    /**
     * Stores validation errors, indexed by paths
     * [ Form_name ] is an array of form errors
     * [path] is a string storing error associated with single field
     *
     * @var array
     */
    private $errors = [];

    /**
     * Paths changed so that they can be used as HTML ids, indexed by paths
     *
     * @var array
     */
    private $translatedPaths = [];

    /**
     * Server paths change indexes so we define maps from current server
     * path to the first one, indexed by work path
     *
     * @var array
     */
    private $systemPaths = [];

    /**
     * Tells whether forms have been validated
     *
     * @var bool
     */
    private $isValidated = true;

    /**
     * Dictionary with user preferences keys
     *
     * @var array|null
     */
    private $userprefsKeys;

    /**
     * Dictionary with disallowed user preferences keys
     *
     * @var array
     */
    private $userprefsDisallow;

    /** @var FormDisplayTemplate */
    private $formDisplayTemplate;

    /**
     * @param ConfigFile $cf Config file instance
     */
    public function __construct(ConfigFile $cf)
    {
        $this->formDisplayTemplate = new FormDisplayTemplate($GLOBALS['config']);
        $this->configFile = $cf;
        // initialize validators
        Validator::getValidators($this->configFile);
    }

    /**
     * Returns {@link ConfigFile} associated with this instance
     *
     * @return ConfigFile
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Registers form in form manager
     *
     * @param string $formName Form name
     * @param array  $form     Form data
     * @param int    $serverId 0 if new server, validation; >= 1 if editing a server
     */
    public function registerForm($formName, array $form, $serverId = null): void
    {
        $this->forms[$formName] = new Form($formName, $form, $this->configFile, $serverId);
        $this->isValidated = false;
        foreach ($this->forms[$formName]->fields as $path) {
            $workPath = $serverId === null
                ? $path
                : str_replace('Servers/1/', 'Servers/' . $serverId . '/', $path);
            $this->systemPaths[$workPath] = $path;
            $this->translatedPaths[$workPath] = str_replace('/', '-', $workPath);
        }
    }

    /**
     * Processes forms, returns true on successful save
     *
     * @param bool $allowPartialSave allows for partial form saving
     *                               on failed validation
     * @param bool $checkFormSubmit  whether check for $_POST['submit_save']
     */
    public function process($allowPartialSave = true, $checkFormSubmit = true): bool
    {
        if ($checkFormSubmit && ! isset($_POST['submit_save'])) {
            return false;
        }

        // save forms
        if (count($this->forms) > 0) {
            return $this->save(array_keys($this->forms), $allowPartialSave);
        }

        return false;
    }

    /**
     * Runs validation for all registered forms
     */
    private function validate(): void
    {
        if ($this->isValidated) {
            return;
        }

        $paths = [];
        $values = [];
        foreach ($this->forms as $form) {
            $paths[] = $form->name;
            // collect values and paths
            foreach ($form->fields as $path) {
                $workPath = array_search($path, $this->systemPaths);
                $values[$path] = $this->configFile->getValue($workPath);
                $paths[] = $path;
            }
        }

        // run validation
        $errors = Validator::validate($this->configFile, $paths, $values, false);

        // change error keys from canonical paths to work paths
        if (is_array($errors) && count($errors) > 0) {
            $this->errors = [];
            foreach ($errors as $path => $errorList) {
                $workPath = array_search($path, $this->systemPaths);
                // field error
                if (! $workPath) {
                    // form error, fix path
                    $workPath = $path;
                }

                $this->errors[$workPath] = $errorList;
            }
        }

        $this->isValidated = true;
    }

    /**
     * Outputs HTML for forms
     *
     * @param bool       $showButtons  whether show submit and reset button
     * @param string     $formAction   action attribute for the form
     * @param array|null $hiddenFields array of form hidden fields (key: field
     *                                 name)
     *
     * @return string HTML for forms
     */
    public function getDisplay(
        $showButtons = true,
        $formAction = null,
        $hiddenFields = null
    ) {
        $js = [];
        $jsDefault = [];

        /**
         * We do validation on page refresh when browser remembers field values,
         * add a field with known value which will be used for checks.
         */
        static $hasCheckPageRefresh = false;
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        $tabs = [];
        foreach ($this->forms as $form) {
            $tabs[$form->name] = Descriptions::get('Form_' . $form->name);
        }

        // validate only when we aren't displaying a "new server" form
        $isNewServer = false;
        foreach ($this->forms as $form) {
            if ($form->index === 0) {
                $isNewServer = true;
                break;
            }
        }

        if (! $isNewServer) {
            $this->validate();
        }

        // user preferences
        $this->loadUserprefsInfo();

        $validators = Validator::getValidators($this->configFile);
        $forms = [];

        foreach ($this->forms as $key => $form) {
            $this->formDisplayTemplate->group = 0;
            $forms[$key] = [
                'name' => $form->name,
                'descriptions' => [
                    'name' => Descriptions::get('Form_' . $form->name, 'name'),
                    'desc' => Descriptions::get('Form_' . $form->name, 'desc'),
                ],
                'errors' => $this->errors[$form->name] ?? null,
                'fields_html' => '',
            ];

            foreach ($form->fields as $field => $path) {
                $workPath = array_search($path, $this->systemPaths);
                $translatedPath = $this->translatedPaths[$workPath];
                // always true/false for user preferences display
                // otherwise null
                $userPrefsAllow = isset($this->userprefsKeys[$path])
                    ? ! isset($this->userprefsDisallow[$path])
                    : null;
                // display input
                $forms[$key]['fields_html'] .= $this->displayFieldInput(
                    $form,
                    $field,
                    $path,
                    $workPath,
                    $translatedPath,
                    true,
                    $userPrefsAllow,
                    $jsDefault
                );
                // register JS validators for this field
                if (! isset($validators[$path])) {
                    continue;
                }

                $this->formDisplayTemplate->addJsValidate($translatedPath, $validators[$path], $js);
            }
        }

        return $this->formDisplayTemplate->display([
            'action' => $formAction,
            'has_check_page_refresh' => $hasCheckPageRefresh,
            'hidden_fields' => (array) $hiddenFields,
            'tabs' => $tabs,
            'forms' => $forms,
            'show_buttons' => $showButtons,
            'js_array' => $js,
            'js_default' => $jsDefault,
        ]);
    }

    /**
     * Prepares data for input field display and outputs HTML code
     *
     * @param Form      $form               Form object
     * @param string    $field              field name as it appears in $form
     * @param string    $systemPath         field path, eg. Servers/1/verbose
     * @param string    $workPath           work path, eg. Servers/4/verbose
     * @param string    $translatedPath     work path changed so that it can be
     *                                      used as XHTML id
     * @param bool      $showRestoreDefault whether show "restore default" button
     *                                      besides the input field
     * @param bool|null $userPrefsAllow     whether user preferences are enabled
     *                                      for this field (null - no support,
     *                                      true/false - enabled/disabled)
     * @param array     $jsDefault          array which stores JavaScript code
     *                                      to be displayed
     *
     * @return string|null HTML for input field
     */
    private function displayFieldInput(
        Form $form,
        $field,
        $systemPath,
        $workPath,
        $translatedPath,
        $showRestoreDefault,
        $userPrefsAllow,
        array &$jsDefault
    ) {
        $name = Descriptions::get($systemPath);
        $description = Descriptions::get($systemPath, 'desc');

        $value = $this->configFile->get($workPath);
        $valueDefault = $this->configFile->getDefault($systemPath);
        $valueIsDefault = false;
        if ($value === null || $value === $valueDefault) {
            $value = $valueDefault;
            $valueIsDefault = true;
        }

        $opts = [
            'doc' => $this->getDocLink($systemPath),
            'show_restore_default' => $showRestoreDefault,
            'userprefs_allow' => $userPrefsAllow,
            'userprefs_comment' => Descriptions::get($systemPath, 'cmt'),
        ];
        if (isset($form->default[$systemPath])) {
            $opts['setvalue'] = (string) $form->default[$systemPath];
        }

        if (isset($this->errors[$workPath])) {
            $opts['errors'] = $this->errors[$workPath];
        }

        $type = '';
        switch ($form->getOptionType($field)) {
            case 'string':
                $type = 'text';
                break;
            case 'short_string':
                $type = 'short_text';
                break;
            case 'double':
            case 'integer':
                $type = 'number_text';
                break;
            case 'boolean':
                $type = 'checkbox';
                break;
            case 'select':
                $type = 'select';
                $opts['values'] = $form->getOptionValueList($form->fields[$field]);
                break;
            case 'array':
                $type = 'list';
                $value = (array) $value;
                $valueDefault = (array) $valueDefault;
                break;
            case 'group':
                // :group:end is changed to :group:end:{unique id} in Form class
                $htmlOutput = '';
                if (mb_substr($field, 7, 4) !== 'end:') {
                    $htmlOutput .= $this->formDisplayTemplate->displayGroupHeader(
                        mb_substr($field, 7)
                    );
                } else {
                    $this->formDisplayTemplate->displayGroupFooter();
                }

                return $htmlOutput;

            case 'NULL':
                trigger_error('Field ' . $systemPath . ' has no type', E_USER_WARNING);

                return null;
        }

        // detect password fields
        if (
            $type === 'text'
            && (mb_substr($translatedPath, -9) === '-password'
               || mb_substr($translatedPath, -4) === 'pass'
               || mb_substr($translatedPath, -4) === 'Pass')
        ) {
            $type = 'password';
        }

        // TrustedProxies requires changes before displaying
        if ($systemPath === 'TrustedProxies') {
            foreach ($value as $ip => &$v) {
                if (preg_match('/^-\d+$/', $ip)) {
                    continue;
                }

                $v = $ip . ': ' . $v;
            }
        }

        $this->setComments($systemPath, $opts);

        // send default value to form's JS
        $jsLine = '\'' . $translatedPath . '\': ';
        switch ($type) {
            case 'text':
            case 'short_text':
            case 'number_text':
            case 'password':
                $jsLine .= '\'' . Sanitize::escapeJsString($valueDefault) . '\'';
                break;
            case 'checkbox':
                $jsLine .= $valueDefault ? 'true' : 'false';
                break;
            case 'select':
                $valueDefaultJs = is_bool($valueDefault)
                ? (int) $valueDefault
                : $valueDefault;
                $jsLine .= '[\'' . Sanitize::escapeJsString($valueDefaultJs) . '\']';
                break;
            case 'list':
                $val = $valueDefault;
                if (isset($val['wrapper_params'])) {
                    unset($val['wrapper_params']);
                }

                $jsLine .= '\'' . Sanitize::escapeJsString(implode("\n", $val))
                . '\'';
                break;
        }

        $jsDefault[] = $jsLine;

        return $this->formDisplayTemplate->displayInput(
            $translatedPath,
            $name,
            $type,
            $value,
            $description,
            $valueIsDefault,
            $opts
        );
    }

    /**
     * Displays errors
     *
     * @return string|null HTML for errors
     */
    public function displayErrors()
    {
        $this->validate();
        if (count($this->errors) === 0) {
            return null;
        }

        $htmlOutput = '';

        foreach ($this->errors as $systemPath => $errorList) {
            if (isset($this->systemPaths[$systemPath])) {
                $name = Descriptions::get($this->systemPaths[$systemPath]);
            } else {
                $name = Descriptions::get('Form_' . $systemPath);
            }

            $htmlOutput .= $this->formDisplayTemplate->displayErrors($name, $errorList);
        }

        return $htmlOutput;
    }

    /**
     * Reverts erroneous fields to their default values
     */
    public function fixErrors(): void
    {
        $this->validate();
        if (count($this->errors) === 0) {
            return;
        }

        $cf = $this->configFile;
        foreach (array_keys($this->errors) as $workPath) {
            if (! isset($this->systemPaths[$workPath])) {
                continue;
            }

            $canonicalPath = $this->systemPaths[$workPath];
            $cf->set($workPath, $cf->getDefault($canonicalPath));
        }
    }

    /**
     * Validates select field and casts $value to correct type
     *
     * @param string|bool $value   Current value
     * @param array       $allowed List of allowed values
     */
    private function validateSelect(&$value, array $allowed): bool
    {
        $valueCmp = is_bool($value)
            ? (int) $value
            : $value;
        foreach (array_keys($allowed) as $vk) {
            // equality comparison only if both values are numeric or not numeric
            // (allows to skip 0 == 'string' equalling to true)
            // or identity (for string-string)
            if (! (($vk == $value && ! (is_numeric($valueCmp) xor is_numeric($vk))) || $vk === $value)) {
                continue;
            }

            // keep boolean value as boolean
            if (! is_bool($value)) {
                // phpcs:ignore Generic.PHP.ForbiddenFunctions
                settype($value, gettype($vk));
            }

            return true;
        }

        return false;
    }

    /**
     * Validates and saves form data to session
     *
     * @param array|string $forms            array of form names
     * @param bool         $allowPartialSave allows for partial form saving on
     *                                       failed validation
     */
    public function save($forms, $allowPartialSave = true): bool
    {
        $result = true;
        $forms = (array) $forms;

        $values = [];
        $toSave = [];
        $isSetupScript = $GLOBALS['config']->get('is_setup');
        if ($isSetupScript) {
            $this->loadUserprefsInfo();
        }

        $this->errors = [];
        foreach ($forms as $formName) {
            if (! isset($this->forms[$formName])) {
                continue;
            }

            $form = $this->forms[$formName];
            // get current server id
            $changeIndex = $form->index === 0
                ? $this->configFile->getServerCount() + 1
                : false;
            // grab POST values
            foreach ($form->fields as $field => $systemPath) {
                $workPath = array_search($systemPath, $this->systemPaths);
                $key = $this->translatedPaths[$workPath];
                $type = (string) $form->getOptionType($field);

                // skip groups
                if ($type === 'group') {
                    continue;
                }

                // ensure the value is set
                if (! isset($_POST[$key])) {
                    // checkboxes aren't set by browsers if they're off
                    if ($type !== 'boolean') {
                        $this->errors[$form->name][] = sprintf(
                            __('Missing data for %s'),
                            '<i>' . Descriptions::get($systemPath) . '</i>'
                        );
                        $result = false;
                        continue;
                    }

                    $_POST[$key] = false;
                }

                // user preferences allow/disallow
                if ($isSetupScript && isset($this->userprefsKeys[$systemPath])) {
                    if (isset($this->userprefsDisallow[$systemPath], $_POST[$key . '-userprefs-allow'])) {
                        unset($this->userprefsDisallow[$systemPath]);
                    } elseif (! isset($_POST[$key . '-userprefs-allow'])) {
                        $this->userprefsDisallow[$systemPath] = true;
                    }
                }

                // cast variables to correct type
                switch ($type) {
                    case 'double':
                        $_POST[$key] = Util::requestString($_POST[$key]);
                        // phpcs:ignore Generic.PHP.ForbiddenFunctions
                        settype($_POST[$key], 'float');
                        break;
                    case 'boolean':
                    case 'integer':
                        if ($_POST[$key] !== '') {
                            $_POST[$key] = Util::requestString($_POST[$key]);
                            // phpcs:ignore Generic.PHP.ForbiddenFunctions
                            settype($_POST[$key], $type);
                        }

                        break;
                    case 'select':
                        $successfullyValidated = $this->validateSelect(
                            $_POST[$key],
                            $form->getOptionValueList($systemPath)
                        );
                        if (! $successfullyValidated) {
                            $this->errors[$workPath][] = __('Incorrect value!');
                            $result = false;
                            // "continue" for the $form->fields foreach-loop
                            continue 2;
                        }

                        break;
                    case 'string':
                    case 'short_string':
                        $_POST[$key] = Util::requestString($_POST[$key]);
                        break;
                    case 'array':
                        // eliminate empty values and ensure we have an array
                        $postValues = is_array($_POST[$key])
                        ? $_POST[$key]
                        : explode("\n", $_POST[$key]);
                        $_POST[$key] = [];
                        $this->fillPostArrayParameters($postValues, $key);
                        break;
                }

                // now we have value with proper type
                $values[$systemPath] = $_POST[$key];
                if ($changeIndex !== false) {
                    $workPath = str_replace(
                        'Servers/' . $form->index . '/',
                        'Servers/' . $changeIndex . '/',
                        $workPath
                    );
                }

                $toSave[$workPath] = $systemPath;
            }
        }

        // save forms
        if (! $allowPartialSave && ! empty($this->errors)) {
            // don't look for non-critical errors
            $this->validate();

            return $result;
        }

        foreach ($toSave as $workPath => $path) {
            // TrustedProxies requires changes before saving
            if ($path === 'TrustedProxies') {
                $proxies = [];
                $i = 0;
                foreach ($values[$path] as $value) {
                    $matches = [];
                    $match = preg_match('/^(.+):(?:[ ]?)(\\w+)$/', $value, $matches);
                    if ($match) {
                        // correct 'IP: HTTP header' pair
                        $ip = trim($matches[1]);
                        $proxies[$ip] = trim($matches[2]);
                    } else {
                        // save also incorrect values
                        $proxies['-' . $i] = $value;
                        $i++;
                    }
                }

                $values[$path] = $proxies;
            }

            $this->configFile->set($workPath, $values[$path], $path);
        }

        if ($isSetupScript) {
            $this->configFile->set(
                'UserprefsDisallow',
                array_keys($this->userprefsDisallow)
            );
        }

        // don't look for non-critical errors
        $this->validate();

        return $result;
    }

    /**
     * Tells whether form validation failed
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Returns link to documentation
     *
     * @param string $path Path to documentation
     *
     * @return string
     */
    public function getDocLink($path)
    {
        $test = mb_substr($path, 0, 6);
        if ($test === 'Import' || $test === 'Export') {
            return '';
        }

        return MySQLDocumentation::getDocumentationLink(
            'config',
            'cfg_' . $this->getOptName($path),
            Sanitize::isSetup() ? '../' : './'
        );
    }

    /**
     * Changes path so it can be used in URLs
     *
     * @param string $path Path
     *
     * @return string
     */
    private function getOptName($path)
    {
        return str_replace(['Servers/1/', '/'], ['Servers/', '_'], $path);
    }

    /**
     * Fills out {@link userprefs_keys} and {@link userprefs_disallow}
     */
    private function loadUserprefsInfo(): void
    {
        if ($this->userprefsKeys !== null) {
            return;
        }

        $this->userprefsKeys = array_flip(UserFormList::getFields());
        // read real config for user preferences display
        $userPrefsDisallow = $GLOBALS['config']->get('is_setup')
            ? $this->configFile->get('UserprefsDisallow', [])
            : $GLOBALS['cfg']['UserprefsDisallow'];
        $this->userprefsDisallow = array_flip($userPrefsDisallow ?? []);
    }

    /**
     * Sets field comments and warnings based on current environment
     *
     * @param string $systemPath Path to settings
     * @param array  $opts       Chosen options
     */
    private function setComments($systemPath, array &$opts): void
    {
        // RecodingEngine - mark unavailable types
        if ($systemPath === 'RecodingEngine') {
            $comment = '';
            if (! function_exists('iconv')) {
                $opts['values']['iconv'] .= ' (' . __('unavailable') . ')';
                $comment = sprintf(
                    __('"%s" requires %s extension'),
                    'iconv',
                    'iconv'
                );
            }

            if (! function_exists('recode_string')) {
                $opts['values']['recode'] .= ' (' . __('unavailable') . ')';
                $comment .= ($comment ? ', ' : '') . sprintf(
                    __('"%s" requires %s extension'),
                    'recode',
                    'recode'
                );
            }

            /* mbstring is always there thanks to polyfill */
            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }

        // ZipDump, GZipDump, BZipDump - check function availability
        if ($systemPath === 'ZipDump' || $systemPath === 'GZipDump' || $systemPath === 'BZipDump') {
            $comment = '';
            $funcs = [
                'ZipDump' => [
                    'zip_open',
                    'gzcompress',
                ],
                'GZipDump' => [
                    'gzopen',
                    'gzencode',
                ],
                'BZipDump' => [
                    'bzopen',
                    'bzcompress',
                ],
            ];
            if (! function_exists($funcs[$systemPath][0])) {
                $comment = sprintf(
                    __(
                        'Compressed import will not work due to missing function %s.'
                    ),
                    $funcs[$systemPath][0]
                );
            }

            if (! function_exists($funcs[$systemPath][1])) {
                $comment .= ($comment ? '; ' : '') . sprintf(
                    __(
                        'Compressed export will not work due to missing function %s.'
                    ),
                    $funcs[$systemPath][1]
                );
            }

            $opts['comment'] = $comment;
            $opts['comment_warning'] = true;
        }

        if ($GLOBALS['config']->get('is_setup')) {
            return;
        }

        if ($systemPath !== 'MaxDbList' && $systemPath !== 'MaxTableList' && $systemPath !== 'QueryHistoryMax') {
            return;
        }

        $opts['comment'] = sprintf(
            __('maximum %s'),
            $GLOBALS['cfg'][$systemPath]
        );
    }

    /**
     * Copy items of an array to $_POST variable
     *
     * @param array  $postValues List of parameters
     * @param string $key        Array key
     */
    private function fillPostArrayParameters(array $postValues, $key): void
    {
        foreach ($postValues as $v) {
            $v = Util::requestString($v);
            if ($v === '') {
                continue;
            }

            $_POST[$key][] = $v;
        }
    }
}
