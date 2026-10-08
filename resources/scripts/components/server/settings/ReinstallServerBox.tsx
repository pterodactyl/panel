import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import { useCurrentServer, useReinstallServer } from '@/api/server/queries';
import { canReinstallServer, SKIPPED_INSTALL_SCRIPT_MESSAGE } from '@/lib/serverStatus';

const ReinstallServerBox = () => {
    const server = useCurrentServer()!;
    const reinstallServer = useReinstallServer();
    const canReinstall = canReinstallServer(server.attributes.status, server.attributes.skip_scripts);

    const reinstall = async (close: () => void) => {
        try {
            await reinstallServer.mutateAsync({ path: { server_uuid: server.attributes.uuid } });
            close();
        } catch {
            // Error toast is handled by the mutation.
        }
    };

    return (
        <TitledGreyBox title='Reinstall Server' className='relative'>
            <p className='text-sm'>
                Reinstalling your server will stop it, and then re-run the installation script that initially set it
                up.&nbsp;
                <strong className='font-medium'>
                    Some files may be deleted or modified during this process, please back up your data before
                    continuing.
                </strong>
            </p>
            {!canReinstall && <p className='mt-3 text-sm text-muted-foreground'>{SKIPPED_INSTALL_SCRIPT_MESSAGE}</p>}
            <Dialog.ConfirmTrigger
                title='Confirm server reinstallation'
                confirm='Yes, reinstall server'
                onConfirmed={(_event, close) => reinstall(close)}
                trigger={({ onClick }) => (
                    <div className='mt-6 text-right'>
                        <Button.Danger isSecondary disabled={!canReinstall} onClick={onClick}>
                            Reinstall Server
                        </Button.Danger>
                    </div>
                )}
            >
                Your server will be stopped and some files may be deleted or modified during this process, are you sure
                you wish to continue?
            </Dialog.ConfirmTrigger>
        </TitledGreyBox>
    );
};

export default ReinstallServerBox;
