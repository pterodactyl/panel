import { Dialog, Translate, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { Close } from './parts';

export default function SteamDiskSpacePrompt({ model }: ViewProps<'server.feature.steamDiskSpace'>) {
    const { t } = useTranslation();

    return (
        <Dialog open={model.open} title={t('Out of available disk space')} onClose={model.close}>
            {model.admin ? (
                <>
                    <p className='mt-4'>
                        {t(
                            'This server has run out of available disk space and cannot complete the install or update process.'
                        )}
                    </p>
                    <p className='mt-4'>
                        <Translate
                            i18nKey='Ensure the machine has enough disk space by typing <code>{{command}}</code> on the machine hosting this server. Delete files or increase the available disk space to resolve the issue.'
                            values={{ command: 'df -h' }}
                            components={{ code: <code className='font-mono bg-muted rounded-sm py-1 px-2' /> }}
                        />
                    </p>
                </>
            ) : (
                <p className='mt-4'>
                    {t(
                        'This server has run out of available disk space and cannot complete the install or update process. Please get in touch with the administrator(s) and inform them of disk space issues.'
                    )}
                </p>
            )}
            <Close onClick={model.close} />
        </Dialog>
    );
}
