import { useState, type ReactNode } from 'react';
import { useStore } from '@tanstack/react-form';
import { useNavigate } from '@tanstack/react-router';
import { useAppForm, Form } from '@/components/form';
import {
    type AdminServer,
    deleteAdminServerInput,
    forceDeleteAdminServerInput,
    useDeleteAdminServer,
    useForceDeleteAdminServer,
} from '@/api/admin/servers/queries';
import { useServerDetail } from '@/components/admin/servers/useServerDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import { Alert } from '@/components/elements/alert';

type DeleteMode = 'safe' | 'force';

interface Props {
    server: AdminServer;
}

interface DeleteServerDialogProps extends Props {
    mode: DeleteMode;
    open: boolean;
    onClose: () => void;
}

function DeleteServerDialog({ server, mode, open, onClose }: DeleteServerDialogProps) {
    const { attributes } = server;
    const navigate = useNavigate();
    const deleteServer = useDeleteAdminServer();
    const forceDeleteServer = useForceDeleteAdminServer();

    const form = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                if (mode === 'force') {
                    await forceDeleteServer.mutateAsync(forceDeleteAdminServerInput(attributes.id, attributes.name));
                } else {
                    await deleteServer.mutateAsync(deleteAdminServerInput(attributes.id, attributes.name));
                }

                await navigate({ to: '/panel/servers' });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    const close = () => {
        form.reset();
        onClose();
    };

    return (
        <Dialog
            open={open}
            title={mode === 'force' ? 'Forcibly delete server' : 'Confirm server deletion'}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={close}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className='text-sm'>
                Deleting a server is a permanent action, it cannot be undone. This will permanently delete the{' '}
                <strong>{attributes.name}</strong> server and remove all associated data.
                {mode === 'force' && (
                    <span className='block mt-2 text-destructive'>
                        Force deleting may leave dangling files on the daemon if it reports an error.
                    </span>
                )}
            </p>
            <Form form={form} className='m-0 mt-6'>
                <form.AppField
                    name='confirm'
                    validators={{
                        onChange: ({ value }) =>
                            value === attributes.name ? undefined : 'The server name must be provided.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`confirm_server_name_${mode}`}
                            label='Confirm Server Name'
                            description='Enter the name of this server to confirm deletion.'
                        />
                    )}
                </form.AppField>
                <div className='mt-6 text-right'>
                    <Button type='button' isSecondary className='mr-2' disabled={isSubmitting} onClick={close}>
                        Cancel
                    </Button>
                    <form.AppForm>
                        <form.SubmitButton color='red'>
                            {mode === 'force' ? 'Force Delete Server' : 'Delete Server'}
                        </form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

const DeleteActionCard = ({
    title,
    warning,
    description,
    action,
}: {
    title: string;
    warning: ReactNode;
    description: ReactNode;
    action: ReactNode;
}) => (
    <TitledGreyBox title={title} className='flex h-full flex-col' contentClassName='flex flex-1 flex-col p-0'>
        <div className='flex flex-1 flex-col gap-3 p-3'>
            <Alert type='danger'>
                <strong className='font-semibold'>Danger!</strong> {warning}
            </Alert>
            <p className='text-sm text-muted-foreground'>{description}</p>
        </div>
        <div className='flex items-center justify-end border-t border-border p-3'>{action}</div>
    </TitledGreyBox>
);

function ServerDeleteContent({ server }: Props) {
    const [mode, setMode] = useState<DeleteMode | null>(null);
    const close = () => setMode(null);

    return (
        <div>
            <DeleteServerDialog server={server} mode='safe' open={mode === 'safe'} onClose={close} />
            <DeleteServerDialog server={server} mode='force' open={mode === 'force'} onClose={close} />

            <div className='grid grid-cols-1 gap-6 lg:grid-cols-2'>
                <DeleteActionCard
                    title='Safely Delete Server'
                    warning={
                        <>
                            Deleting a server is an irreversible action. <strong>All server data</strong> (including
                            files and users) will be removed from the system.
                        </>
                    }
                    description='This action will attempt to delete the server from both the panel and daemon. If either one reports an error the action will be cancelled.'
                    action={
                        <Button color='red' className='whitespace-nowrap' onClick={() => setMode('safe')}>
                            Safely Delete This Server
                        </Button>
                    }
                />

                <DeleteActionCard
                    title='Force Delete Server'
                    warning={
                        <>
                            Deleting a server is an irreversible action. <strong>All server data</strong> will be
                            removed. This method may leave dangling files on your daemon if it reports an error.
                        </>
                    }
                    description='This action will attempt to delete the server from both the panel and daemon. If the daemon does not respond, or reports an error, the deletion will continue.'
                    action={
                        <Button color='red' className='whitespace-nowrap' onClick={() => setMode('force')}>
                            Forcibly Delete This Server
                        </Button>
                    }
                />
            </div>
        </div>
    );
}

export default function ServerDeleteTab() {
    const { server } = useServerDetail();

    return <ServerDeleteContent server={server} />;
}
