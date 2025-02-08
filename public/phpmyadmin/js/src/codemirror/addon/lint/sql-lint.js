CodeMirror.sqlLint = function (text, updateLinting, options, cm) {
    // Skipping check if text box is empty.
    if (text.trim() === '') {
        updateLinting(cm, []);
        return;
    }

    function handleResponse (response) {
        var found = [];
        for (var idx in response) {
            found.push({
                // eslint-disable-next-line new-cap
                from: CodeMirror.Pos(
                    response[idx].fromLine, response[idx].fromColumn
                ),
                // eslint-disable-next-line new-cap
                to: CodeMirror.Pos(
                    response[idx].toLine, response[idx].toColumn
                ),
                messageHTML: response[idx].message,
                severity : response[idx].severity
            });
        }

        updateLinting(cm, found);
    }

    $.ajax({
        method: 'POST',
        url: 'index.php?route=/lint',
        dataType: 'json',
        data: {
            'sql_query': text,
            'server': CommonParams.get('server'),
            'options': options.lintOptions,
            'no_history': true,
            'ajax_request': true,
        },
        success: handleResponse
    });
};
