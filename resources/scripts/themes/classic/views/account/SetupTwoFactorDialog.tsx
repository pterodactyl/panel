import {
    Alert,
    Button,
    CopyOnClick,
    Dialog,
    Input,
    QrCode,
    Spinner,
    Tooltip,
    type AccountTwoFactorModel,
    type TwoFactorSetupModel,
} from '@/sdk';

function SecretQrCode({ setup }: { setup: TwoFactorSetupModel }) {
    if (setup.error) {
        return <span className='text-sm text-muted-foreground text-center'>Unable to load QR code.</span>;
    }

    return setup.qrCode === null ? <Spinner /> : <QrCode value={setup.qrCode} className='w-full h-full shadow-none' />;
}

function SetupForm({ setup, onClose }: { setup: TwoFactorSetupModel; onClose: () => void }) {
    const { code, password } = setup.fields;
    const complete = password.fieldProps.value.length > 0 && code.fieldProps.value.length === 6;

    return (
        <form
            id='enable-totp-form'
            onSubmit={(event) => {
                event.preventDefault();
                event.stopPropagation();
                setup.submit();
            }}
        >
            <div className='flex items-center justify-center w-56 h-56 p-2 bg-card shadow-sm mx-auto mt-6'>
                <SecretQrCode setup={setup} />
            </div>
            <CopyOnClick text={setup.secret ?? undefined}>
                <p className='font-mono text-sm text-foreground text-center mt-2'>
                    {setup.secret?.match(/.{1,4}/g)?.join(' ') || 'Loading...'}
                </p>
            </CopyOnClick>
            {setup.error && (
                <div className='mt-4 space-y-3'>
                    <Alert type='danger'>{setup.error}</Alert>
                    <Button.Text type='button' onClick={setup.retry}>
                        Retry
                    </Button.Text>
                </div>
            )}
            <p id='totp-code-description' className='mt-6'>
                Scan the QR code above using the two-step authentication app of your choice. Then, enter the 6-digit
                code generated into the field below.
            </p>
            <Input {...code.fieldProps} aria-labelledby='totp-code-description' className='mt-3' placeholder='000000' />
            <label htmlFor='totp-password' className='block mt-3'>
                Account Password
            </label>
            <Input {...password.fieldProps} id='totp-password' className='mt-1' />
            <Dialog.Footer>
                <Button.Text onClick={onClose}>Cancel</Button.Text>
                <Tooltip
                    disabled={complete}
                    content={
                        setup.secret
                            ? 'You must enter the 6-digit code and your password to continue.'
                            : 'Waiting for QR code to load...'
                    }
                    delay={100}
                >
                    <Button
                        disabled={!setup.canSubmit}
                        isLoading={setup.submitting}
                        type='submit'
                        form='enable-totp-form'
                    >
                        Enable
                    </Button>
                </Tooltip>
            </Dialog.Footer>
        </form>
    );
}

export default function SetupTwoFactorDialog({ model }: { model: AccountTwoFactorModel }) {
    return (
        <Dialog
            open={model.dialog === 'enable'}
            onClose={model.closeDialog}
            preventExternalClose={model.setup.submitting}
            hideCloseIcon={model.setup.submitting}
            title='Enable Two-Step Verification'
            description="Help protect your account from unauthorized access. You'll be prompted for a verification code each time you sign in."
        >
            <SetupForm setup={model.setup} onClose={model.closeDialog} />
        </Dialog>
    );
}
