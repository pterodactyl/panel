import { Button, Dialog, Input, Tooltip, type AccountTwoFactorModel } from '@/sdk';

export default function DisableTwoFactorDialog({ model }: { model: AccountTwoFactorModel }) {
    const { disable } = model;
    const { password } = disable.fields;

    return (
        <Dialog
            open={model.dialog === 'disable'}
            onClose={model.closeDialog}
            preventExternalClose={disable.submitting}
            hideCloseIcon={disable.submitting}
            title='Disable Two-Step Verification'
            description='Disabling two-step verification will make your account less secure.'
        >
            <form
                id='disable-totp-form'
                className='mt-6'
                onSubmit={(event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    disable.submit();
                }}
            >
                <label className='block pb-1' htmlFor='totp-password'>
                    Password
                </label>
                <Input {...password.fieldProps} id='totp-password' />
                <Dialog.Footer>
                    <Button.Text onClick={model.closeDialog}>Cancel</Button.Text>
                    <Tooltip
                        delay={100}
                        disabled={password.fieldProps.value.length > 0}
                        content='You must enter your account password to continue.'
                    >
                        <Button.Danger
                            type='submit'
                            form='disable-totp-form'
                            disabled={!disable.canSubmit}
                            isLoading={disable.submitting}
                        >
                            Disable
                        </Button.Danger>
                    </Tooltip>
                </Dialog.Footer>
            </form>
        </Dialog>
    );
}
