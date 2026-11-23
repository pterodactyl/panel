import { Dialog, Translate, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { Close, LimitIcon } from './parts';

const pidLimitError = 'pthread_create failed, Possibly out of memory or process/resource limits reached';

export default function PidLimitPrompt({ model }: ViewProps<'server.feature.pidLimit'>) {
    const { t } = useTranslation();

    return (
        <Dialog
            open={model.open}
            title={model.admin ? t('Memory or process limit reached') : t('Possible resource limit reached')}
            onClose={model.close}
        >
            <LimitIcon />
            {model.admin ? (
                <>
                    <p className='mt-4'>{t('This server has reached the maximum process or memory limit.')}</p>
                    <p className='mt-4'>
                        <Translate
                            i18nKey='Increasing <code>{{setting}}</code> in the wings configuration, <code>{{file}}</code>, might help resolve this issue.'
                            values={{ setting: 'container_pid_limit', file: 'config.yml' }}
                            components={{ code: <code className='font-mono bg-muted' /> }}
                        />
                    </p>
                    <p className='mt-4'>
                        <b>{t('Note: Wings must be restarted for the configuration file changes to take effect')}</b>
                    </p>
                </>
            ) : (
                <>
                    <p className='mt-4'>
                        {t(
                            'This server is attempting to use more resources than allocated. Please contact the administrator and give them the error below.'
                        )}
                    </p>
                    <p className='mt-4'>
                        <code className='font-mono bg-muted'>{pidLimitError}</code>
                    </p>
                </>
            )}
            <Close onClick={model.close} />
        </Dialog>
    );
}
