import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import { useCurrentUser } from '@/api/account/queries';
import { useConsolePatternTrigger } from '@/components/server/features/useConsolePatternTrigger';

const steamDiskSpaceErrors = ['steamcmd needs 250mb of free disk space to update', '0x202 after update job'];

const SteamDiskSpaceFeature = () => {
    const dialog = useConsolePatternTrigger(steamDiskSpaceErrors);
    const loading = false;
    const isAdmin = useCurrentUser().rootAdmin;

    return (
        <Dialog
            open={dialog.open}
            title='Out of available disk space'
            onClose={dialog.hide}
            preventExternalClose={loading}
            hideCloseIcon={loading}
        >
            <SpinnerOverlay visible={loading} />
            {isAdmin ? (
                <>
                    <p className='mt-4'>
                        This server has run out of available disk space and cannot complete the install or update
                        process.
                    </p>
                    <p className='mt-4'>
                        Ensure the machine has enough disk space by typing{' '}
                        <code className='font-mono bg-muted rounded-sm py-1 px-2'>df -h</code> on the machine hosting
                        this server. Delete files or increase the available disk space to resolve the issue.
                    </p>
                    <div className='mt-8 sm:flex items-center justify-end'>
                        <Button onClick={dialog.hide} className='w-full sm:w-auto border-transparent'>
                            Close
                        </Button>
                    </div>
                </>
            ) : (
                <>
                    <p className='mt-4'>
                        This server has run out of available disk space and cannot complete the install or update
                        process. Please get in touch with the administrator(s) and inform them of disk space issues.
                    </p>
                    <div className='mt-8 sm:flex items-center justify-end'>
                        <Button onClick={dialog.hide} className='w-full sm:w-auto border-transparent'>
                            Close
                        </Button>
                    </div>
                </>
            )}
        </Dialog>
    );
};

export default SteamDiskSpaceFeature;
