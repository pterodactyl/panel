class Allocation {
    constructor() {
        $('[data-action="addSelection"]').on('click', () => {
            this.updateMassActions();
        });

        $('[data-action="selectAll"]').on('click', () => {
            $('input.select-file').not(':disabled').prop('checked', (i, val) => {
                return !val;
            });

            this.updateMassActions();
        });

        $('[data-action="selective-deletion"]').on('mousedown', () => {
            this.deleteSelected();
        });
    }

    updateMassActions() {
        if ($('input.select-file:checked').length > 0) {
            $('#mass_actions').removeClass('disabled');
        } else {
            $('#mass_actions').addClass('disabled');
        }
    }

    deleteSelected() {
        let selectedIds = [];
        let selectedItems = [];
        let selectedItemsElements = [];

        $('input.select-file:checked').each(function () {
            const $parent = $($(this).closest('tr'));
            const id = $parent.find('[data-action="deallocate"]').data('id');
            const $ip = $parent.find('td[data-identifier="ip"]');
            const $port = $parent.find('td[data-identifier="port"]');
            const block = `${$ip.text()}:${$port.text()}`;

            selectedIds.push({
                id: id
            });
            selectedItems.push(block);
            selectedItemsElements.push($parent);
        });

        if (selectedItems.length !== 0) {
            let formattedItems = "";
            let i = 0;
            $.each(selectedItems, function (key, value) {
                formattedItems += ("<code>" + value + "</code>, ");
                i++;
                return i < 5;
            });

            formattedItems = formattedItems.slice(0, -2);
            if (selectedItems.length > 5) {
                formattedItems += ', and ' + (selectedItems.length - 5) + ' other(s)';
            }

            swal({
                type: 'warning',
                title: '',
                text: 'Are you sure you want to delete the following allocations: ' + formattedItems + '?',
                html: true,
                showCancelButton: true,
                showConfirmButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, () => {
                $.ajax({
                    method: 'DELETE',
                    url: Router.route('admin.nodes.view.allocation.removeMultiple', {
                        node: Pterodactyl.node.id
                    }),
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: JSON.stringify({
                        allocations: selectedIds
                    }),
                    contentType: 'application/json',
                    processData: false
                }).done(data => {
                    $('#file_listing input:checked').each(function () {
                        $(this).prop('checked', false);
                    });

                    $.each(selectedItemsElements, function () {
                        $(this).addClass('warning').delay(200).fadeOut();
                    });

                    swal({
                        type: 'success',
                        title: 'Allocations Deleted'
                    });
                }).fail(jqXHR => {
                    console.error(jqXHR);
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        html: true,
                        text: 'An error occurred while attempting to delete these allocations. Please try again.',
                    });
                });
            });
        } else {
            swal({
                type: 'warning',
                title: '',
                text: 'Please select allocation(s) to delete.',
            });
        }
    }
}

window.Allocation = new Allocation();
