$(document).ready(function() {
    $('#user').select2({
        ajax: {
            url: Router.route('admin.users.json'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function (data, params) {
                return { results: data };
            },
            cache: true,
        },
        escapeMarkup: function (markup) { return markup; },
        minimumInputLength: 2,
        templateResult: function (data) {
            if (data.loading) return data.text;

            return '<div class="user-block"> \
                <img class="img-circle img-bordered-xs" src="https://www.gravatar.com/avatar/' + data.md5 + '?s=120" alt="User Image"> \
                <span class="username"> \
                    <a href="#">' + data.name_first + ' ' + data.name_last +'</a> \
                </span> \
                <span class="description"><strong>' + data.email + '</strong> - ' + data.username + '</span> \
            </div>';
        },
        templateSelection: function (data) {
            return '<div> \
                <span> \
                    <img class="img-rounded img-bordered-xs" src="https://www.gravatar.com/avatar/' + data.md5 + '?s=120" style="height:28px;margin-top:-4px;" alt="User Image"> \
                </span> \
                <span style="padding-left:5px;"> \
                    ' + data.name_first + ' ' + data.name_last + ' (<strong>' + data.email + '</strong>) \
                </span> \
            </div>';
        }
    });
});
