import { useState } from 'react';
import { useCurrentServerUuid } from '@/api/server/queries';
import { useServerStatus, useSocketInstance } from '@/state/server';
import { Dialog, useDialogState } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import Select from '@/components/ui/Select';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import Can from '@/components/elements/Can';
import InputSpinner from '@/components/elements/InputSpinner';
import { useServerStartup, useSetSelectedDockerImage } from '@/api/server/startup/queries';

const MATCH_ERRORS = [
    'minecraft 1.17 requires running the server with java 16 or above',
    'minecraft 1.18 requires running the server with java 17 or above',
    'java.lang.unsupportedclassversionerror',
    'unsupported major.minor version',
    'has been compiled by a more recent version of the java runtime',
];

const JavaVersionModalFeature = () => {
    const dialog = useDialogState();
    const [selectedVersionOverride, setSelectedVersionOverride] = useState('');

    const uuid = useCurrentServerUuid()!;
    const status = useServerStatus();
    const instance = useSocketInstance();
    const updateDockerImage = useSetSelectedDockerImage(uuid);
    const loading = updateDockerImage.isPending;

    const { data, isFetching } = useServerStartup(uuid, { enabled: dialog.open });
    const selectedVersion = selectedVersionOverride || Object.values(data?.meta?.docker_images ?? {})[0] || '';

    useWebsocketEvent(SocketEvent.CONSOLE_OUTPUT, (data) => {
        if (status === 'running') {
            return;
        }

        if (MATCH_ERRORS.some((p) => data.toLowerCase().includes(p.toLowerCase()))) {
            setSelectedVersionOverride('');
            dialog.show();
        }
    });

    const updateJava = () => {
        updateDockerImage
            .mutateAsync({ path: { server_uuid: uuid }, body: { docker_image: selectedVersion } })
            .then(() => {
                if (status === 'offline' && instance) {
                    instance.send(SocketRequest.SET_STATE, 'restart');
                }

                dialog.hide();
            })
            .catch(() => {});
    };

    return (
        <Dialog
            open={dialog.open}
            title='Unsupported Java version'
            onClose={dialog.hide}
            preventExternalClose={loading}
            hideCloseIcon={loading}
        >
            <SpinnerOverlay visible={loading} />
            <p className='mt-4'>
                This server is currently running an unsupported version of Java and cannot be started.
                <Can action='startup.docker-image'>
                    &nbsp;Please select a supported version from the list below to continue starting the server.
                </Can>
            </p>
            <Can action='startup.docker-image'>
                <div className='mt-4'>
                    <InputSpinner visible={!data || isFetching}>
                        <Select
                            disabled={!data}
                            value={selectedVersion}
                            onChange={(value) => setSelectedVersionOverride(String(value))}
                            options={
                                data
                                    ? Object.entries(data.meta?.docker_images ?? {}).map(([label, value]) => ({
                                          value,
                                          label,
                                      }))
                                    : []
                            }
                        />
                    </InputSpinner>
                </div>
            </Can>
            <div className='mt-8 flex flex-col sm:flex-row justify-end sm:space-x-4 space-y-4 sm:space-y-0'>
                <Button isSecondary onClick={dialog.hide} className='w-full sm:w-auto'>
                    Cancel
                </Button>
                <Can action='startup.docker-image'>
                    <Button onClick={updateJava} className='w-full sm:w-auto'>
                        Update Docker Image
                    </Button>
                </Can>
            </div>
        </Dialog>
    );
};

export default JavaVersionModalFeature;
