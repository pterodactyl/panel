import { Button, Card, cn, Dialog, Translate, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function ServerReinstall({ model, className }: ViewProps<'server.reinstall'>) {
    const { t } = useTranslation();

    if (!model.allowed) {
        return null;
    }

    return (
        <Card title={t('Reinstall Server')} className={cn('relative', className)}>
            <p className='text-sm'>
                <Translate
                    i18nKey='Reinstalling your server will stop it, and then re-run the installation script that initially set it up. <bold>Some files may be deleted or modified during this process, please back up your data before continuing.</bold>'
                    components={{ bold: <strong className='font-medium' /> }}
                />
            </p>
            {model.unavailable && <p className='mt-3 text-sm text-muted-foreground'>{model.unavailable}</p>}
            <Dialog.ConfirmTrigger
                title={t('Confirm server reinstallation')}
                confirm={t('Yes, reinstall server')}
                onConfirmed={async (_event, close) => {
                    if (await model.reinstall()) {
                        close();
                    }
                }}
                trigger={({ onClick }) => (
                    <div className='mt-6 text-right'>
                        <Button.Danger isSecondary disabled={model.unavailable !== null} onClick={onClick}>
                            {t('Reinstall Server')}
                        </Button.Danger>
                    </div>
                )}
            >
                {t(
                    'Your server will be stopped and some files may be deleted or modified during this process, are you sure you wish to continue?'
                )}
            </Dialog.ConfirmTrigger>
        </Card>
    );
}
