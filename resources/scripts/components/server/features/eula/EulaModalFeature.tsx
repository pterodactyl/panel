import { useServerStatus, useSocketInstance } from '@/state/server';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import { SocketRequest } from '@/components/server/events';
import { useCurrentServerUuid } from '@/api/server/queries';
import { useSaveFileContent, writeFileContentsInput } from '@/api/server/files/queries';
import { useConsolePatternTrigger } from '@/components/server/features/useConsolePatternTrigger';

const eulaErrors = ['you need to agree to the eula in order to run the server'];

const EulaModalFeature = () => {
    const dialog = useConsolePatternTrigger(eulaErrors);

    const uuid = useCurrentServerUuid()!;
    const status = useServerStatus();
    const instance = useSocketInstance();
    const saveFileContent = useSaveFileContent();
    const loading = saveFileContent.isPending;

    const onAcceptEULA = () => {
        saveFileContent
            .mutateAsync(writeFileContentsInput(uuid, 'eula.txt', 'eula=true'))
            .then(() => {
                if (status === 'offline' && instance) {
                    instance.send(SocketRequest.SET_STATE, 'restart');
                }

                dialog.hide();
            })
            .catch(() => {
                // Error toast is handled by the mutation.
            });
    };

    return (
        <Dialog
            open={dialog.open}
            title={<>Accept Minecraft&reg; EULA</>}
            onClose={dialog.hide}
            preventExternalClose={loading}
            hideCloseIcon={loading}
        >
            <SpinnerOverlay visible={loading} />
            <p className={'text-foreground'}>
                By pressing {'"I Accept"'} below you are indicating your agreement to the&nbsp;
                <a
                    target={'_blank'}
                    className={'text-accent underline transition-colors duration-150 hover:text-accent/80'}
                    rel={'noreferrer noopener'}
                    href='https://www.minecraft.net/eula'
                >
                    Minecraft&reg; EULA
                </a>
                .
            </p>
            <div className={'mt-8 sm:flex items-center justify-end'}>
                <Button isSecondary onClick={dialog.hide} className={'w-full sm:w-auto border-transparent'}>
                    Cancel
                </Button>
                <Button onClick={onAcceptEULA} className={'mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto'}>
                    I Accept
                </Button>
            </div>
        </Dialog>
    );
};

export default EulaModalFeature;
