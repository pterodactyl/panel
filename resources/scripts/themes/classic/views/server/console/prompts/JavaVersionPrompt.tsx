import { Button, Dialog, InputSpinner, Select, SpinnerOverlay, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function JavaVersionPrompt({ model }: ViewProps<'server.feature.javaVersion'>) {
    const { t } = useTranslation();

    return (
        <Dialog
            open={model.open}
            title={t('Unsupported Java version')}
            onClose={model.close}
            preventExternalClose={model.pending}
            hideCloseIcon={model.pending}
        >
            <SpinnerOverlay visible={model.pending} />
            <p className='mt-4'>
                {t('This server is currently running an unsupported version of Java and cannot be started.')}
                {model.canChangeImage && (
                    <>
                        {'\u00a0'}
                        {t('Please select a supported version from the list below to continue starting the server.')}
                    </>
                )}
            </p>
            {model.canChangeImage && (
                <div className='mt-4'>
                    <InputSpinner visible={model.loading}>
                        <Select
                            disabled={!model.images}
                            value={model.selected}
                            onChange={(value) => model.select(String(value))}
                            options={(model.images ?? []).map(({ label, value }) => ({ value, label }))}
                        />
                    </InputSpinner>
                </div>
            )}
            <div className='mt-8 flex flex-col sm:flex-row justify-end sm:space-x-4 space-y-4 sm:space-y-0'>
                <Button isSecondary onClick={model.close} className='w-full sm:w-auto'>
                    {t('Cancel')}
                </Button>
                {model.canChangeImage && (
                    <Button onClick={() => void model.update().catch(() => {})} className='w-full sm:w-auto'>
                        {t('Update Docker Image')}
                    </Button>
                )}
            </div>
        </Dialog>
    );
}
