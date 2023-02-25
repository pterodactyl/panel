<!-- The Modal -->
<div class="modal fade" id="redeemVoucherModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">{{__('Redeem voucher code')}}</h4>

                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <form id="redeemVoucherForm" onsubmit="return false" method="post" action="{{route('voucher.redeem')}}">
                    <div class="form-group">
                        <label for="redeemVoucherCode">{{__('Code')}}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="fas fa-money-check-alt"></i>
                                </div>
                            </div>
                            <input id="redeemVoucherCode" name="code" placeholder="SUMMER" type="text"
                                   class="form-control">
                        </div>
                        <span id="redeemVoucherCodeError" class="text-danger"></span>
                        <span id="redeemVoucherCodeSuccess" class="text-success"></span>
                    </div>
                </form>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">{{__('Close')}}</button>
                <button name="submit" id="redeemVoucherSubmit" onclick="redeemVoucherCode()" type="button"
                        class="btn btn-primary">{{__('Redeem')}}
                </button>
            </div>

        </div>
    </div>
</div>


<script>
    function redeemVoucherCode() {
        let form = document.getElementById('redeemVoucherForm')
        let button = document.getElementById('redeemVoucherSubmit')
        let input = document.getElementById('redeemVoucherCode')

        console.log(form.method, form.action)
        button.disabled = true

        $.ajax({
            method: form.method,
            url: form.action,
            dataType: 'json',
            data: {
                "_token": "{{ csrf_token() }}",
                code: input.value
            },
            success: function (response) {
                resetForm()
                redeemVoucherSetSuccess(response)
                setTimeout(() => {
                    $('#redeemVoucherModal').modal('toggle');
                } , 1500)
            },
            error: function (jqXHR, textStatus, errorThrown) {
                resetForm()
                redeemVoucherSetError(jqXHR)
                console.error(jqXHR.responseJSON)
            },

        })
    }

    function resetForm() {
        let button = document.getElementById('redeemVoucherSubmit')
        let input = document.getElementById('redeemVoucherCode')
        let successLabel = document.getElementById('redeemVoucherCodeSuccess')
        let errorLabel = document.getElementById('redeemVoucherCodeError')

        input.classList.remove('is-invalid')
        input.classList.remove('is-valid')
        successLabel.innerHTML = ''
        errorLabel.innerHTML = ''
        button.disabled = false
    }

    function redeemVoucherSetError(error) {
        let input = document.getElementById('redeemVoucherCode')
        let errorLabel = document.getElementById('redeemVoucherCodeError')

        input.classList.add("is-invalid")

        errorLabel.innerHTML = error.status === 422 ? error.responseJSON.errors.code[0] : error.responseJSON.message
    }

    function redeemVoucherSetSuccess(response) {
        let input = document.getElementById('redeemVoucherCode')
        let successLabel = document.getElementById('redeemVoucherCodeSuccess')

        successLabel.innerHTML = response.success
        input.classList.remove('is-invalid')
        input.classList.add('is-valid')
    }
</script>
