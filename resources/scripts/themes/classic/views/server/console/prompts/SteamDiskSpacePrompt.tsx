import { Dialog } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { Close } from './parts';

export default function SteamDiskSpacePrompt({ model }: ViewProps<'server.feature.steamDiskSpace'>) {
    return (
        <Dialog open={model.open} title='Out of available disk space' onClose={model.close}>
            {model.admin ? (
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
                </>
            ) : (
                <p className='mt-4'>
                    This server has run out of available disk space and cannot complete the install or update process.
                    Please get in touch with the administrator(s) and inform them of disk space issues.
                </p>
            )}
            <Close onClick={model.close} />
        </Dialog>
    );
}
