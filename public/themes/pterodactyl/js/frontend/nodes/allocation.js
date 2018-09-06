class Allocation {
    selectItem() {
        $('[data-action="addSelection"]').on('click', event => {
            event.preventDefault();
        });
    }

    selectAll() {
        $('[data-action="selectAll"]').on('click', event => {
            event.preventDefault();
        });
    }

    selectiveDeletion() {
        $('[data-action="selective-deletion"]').on('mousedown', () => {
            deleteSelected();
        });
    }

    deleteSelected() {
        let selectedItems = [];
        let selectedItemsElements = [];
        let parent;
        let ipBlock;
        let portBlock;
        let delLocation;

        $('#file_listing input[data-action="addSelection"]:checked').each(function () {
            parent = $(this).closest('tr');
            ipBlock = $(parent).find('td[data-identifier="ip"]');
            portBlock = $(parent).find('td[data-identifier="port"');

            delLocation = `${ipBlock.text()}:${portBlock.text()}`;

            selectedItems.push(delLocation);
            selectedItemsElements.push(parent);
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
                    url: Router.route('admin.nodes.view.allocation.removeMultiple', { node: Pterodactyl.node.id, allocation: selectedItems }),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') }
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
