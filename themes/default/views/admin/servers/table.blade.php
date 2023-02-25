<table id="datatable" class="table table-striped">
    <thead>
        <tr>
            <th width="20"></th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Server id') }}</th>
            <th>{{ __('Config') }}</th>
            <th>{{ __('Suspended at') }}</th>
            <th>{{ __('Created at') }}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<script>
    function submitResult() {
        return confirm("{{ __('Are you sure you wish to delete?') }}") !== false;
    }

    document.addEventListener("DOMContentLoaded", function() {
        $('#datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.3/i18n/{{ config('SETTINGS::LOCALE:DATATABLES') }}.json'
            },
            processing: true,
            serverSide: true,
            stateSave: true,
            ajax: "{{ route('admin.servers.datatable') }}{{ $filter ?? '' }}",
            order: [
                [6, "desc"]
            ],
            columns: [{
                    data: 'status',
                    name: 'servers.suspended',
                    sortable: false
                },
                {
                    data: 'name'
                },
                {
                    data: 'user',
                    name: 'user.name',
                },
                {
                    data: 'identifier'
                },
                {
                    data: 'resources',
                    name: 'product.name',
                    sortable: false
                },
                {
                    data: 'suspended'
                },
                {
                    data: 'created_at'
                },
                {
                    data: 'actions',
                    sortable: false
                },
            ],
            fnDrawCallback: function(oSettings) {
                $('[data-toggle="popover"]').popover();
            }
        });
    });
</script>
