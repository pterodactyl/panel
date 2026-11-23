import { Button, Dialog, SpinnerOverlay, Translate, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { cancelClass } from './parts';

export default function EulaPrompt({ model }: ViewProps<'server.feature.eula'>) {
    const { t } = useTranslation();

    return (
        <Dialog
            open={model.open}
            title={t('Accept Minecraft® EULA')}
            onClose={model.close}
            preventExternalClose={model.pending}
            hideCloseIcon={model.pending}
        >
            <SpinnerOverlay visible={model.pending} />
            <p className='text-foreground'>
                <Translate
                    i18nKey='By pressing "I Accept" below you are indicating your agreement to the <link>Minecraft® EULA</link>.'
                    components={{
                        link: (
                            <a
                                target='_blank'
                                className='text-accent underline transition-colors duration-150 hover:text-accent/80'
                                rel='noreferrer noopener'
                                href='https://www.minecraft.net/eula'
                            />
                        ),
                    }}
                />
            </p>
            <div className='mt-8 sm:flex items-center justify-end'>
                <Button isSecondary onClick={model.close} className={cancelClass}>
                    {t('Cancel')}
                </Button>
                <Button
                    onClick={() => void model.accept().catch(() => {})}
                    className='mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto'
                >
                    {t('I Accept')}
                </Button>
            </div>
        </Dialog>
    );
}
