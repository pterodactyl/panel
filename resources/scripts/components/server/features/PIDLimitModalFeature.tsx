import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import Icon from '@/components/elements/Icon';
import { TriangleAlert } from 'lucide-react';
import { useCurrentUser } from '@/api/account/queries';
import { useConsolePatternTrigger } from '@/components/server/features/useConsolePatternTrigger';

const pidLimitErrors = [
    'pthread_create failed',
    'failed to create thread',
    'unable to create thread',
    'unable to create native thread',
    'unable to create new native thread',
    'exception in thread "craft async scheduler management thread"',
];

const PIDLimitModalFeature = () => {
    const dialog = useConsolePatternTrigger(pidLimitErrors);
    const loading = false;
    const isAdmin = useCurrentUser().rootAdmin;

    return (
        <Dialog
            open={dialog.open}
            title={isAdmin ? 'Memory or process limit reached' : 'Possible resource limit reached'}
            onClose={dialog.hide}
            preventExternalClose={loading}
            hideCloseIcon={loading}
        >
            <SpinnerOverlay visible={loading} />
            {isAdmin ? (
                <>
                    <div className={'mt-4 sm:flex items-center'}>
                        <Icon icon={TriangleAlert} className={'pr-4 text-warning'} size={64} />
                    </div>
                    <p className={'mt-4'}>This server has reached the maximum process or memory limit.</p>
                    <p className={'mt-4'}>
                        Increasing <code className={'font-mono bg-muted'}>container_pid_limit</code> in the wings
                        configuration, <code className={'font-mono bg-muted'}>config.yml</code>, might help resolve this
                        issue.
                    </p>
                    <p className={'mt-4'}>
                        <b>Note: Wings must be restarted for the configuration file changes to take effect</b>
                    </p>
                    <div className={'mt-8 sm:flex items-center justify-end'}>
                        <Button onClick={dialog.hide} className={'w-full sm:w-auto border-transparent'}>
                            Close
                        </Button>
                    </div>
                </>
            ) : (
                <>
                    <div className={'mt-4 sm:flex items-center'}>
                        <Icon icon={TriangleAlert} className={'pr-4 text-warning'} size={64} />
                    </div>
                    <p className={'mt-4'}>
                        This server is attempting to use more resources than allocated. Please contact the administrator
                        and give them the error below.
                    </p>
                    <p className={'mt-4'}>
                        <code className={'font-mono bg-muted'}>
                            pthread_create failed, Possibly out of memory or process/resource limits reached
                        </code>
                    </p>
                    <div className={'mt-8 sm:flex items-center justify-end'}>
                        <Button onClick={dialog.hide} className={'w-full sm:w-auto border-transparent'}>
                            Close
                        </Button>
                    </div>
                </>
            )}
        </Dialog>
    );
};

export default PIDLimitModalFeature;
