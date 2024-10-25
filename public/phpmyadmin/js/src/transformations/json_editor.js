/**
 * JSON syntax highlighting transformation plugin
 *
 * @package PhpMyAdmin
 */
AJAX.registerOnload('transformations/json_editor.js', function () {
    $('textarea.transform_json_editor').each(function () {
        CodeMirror.fromTextArea(this, {
            lineNumbers: true,
            matchBrackets: true,
            indentUnit: 4,
            mode: 'application/json',
            lineWrapping: true
        });
    });
});
