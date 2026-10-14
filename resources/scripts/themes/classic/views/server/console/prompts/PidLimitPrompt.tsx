import { Dialog } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { Close, LimitIcon } from './parts';

export default function PidLimitPrompt({ model }: ViewProps<'server.feature.pidLimit'>) {
    return (
        <Dialog
            open={model.open}
            title={model.admin ? 'Memory or process limit reached' : 'Possible resource limit reached'}
            onClose={model.close}
        >
            <LimitIcon />
            {model.admin ? (
                <>
                    <p className='mt-4'>This server has reached the maximum process or memory limit.</p>
                    <p className='mt-4'>
                        Increasing <code className='font-mono bg-muted'>container_pid_limit</code> in the wings
                        configuration, <code className='font-mono bg-muted'>config.yml</code>, might help resolve this
                        issue.
                    </p>
                    <p className='mt-4'>
                        <b>Note: Wings must be restarted for the configuration file changes to take effect</b>
                    </p>
                </>
            ) : (
                <>
                    <p className='mt-4'>
                        This server is attempting to use more resources than allocated. Please contact the administrator
                        and give them the error below.
                    </p>
                    <p className='mt-4'>
                        <code className='font-mono bg-muted'>
                            pthread_create failed, Possibly out of memory or process/resource limits reached
                        </code>
                    </p>
                </>
            )}
            <Close onClick={model.close} />
        </Dialog>
    );
}
