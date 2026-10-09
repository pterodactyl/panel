import React from 'react';
import {
    type AdminServer,
    reinstallAdminServerInput,
    toggleAdminServerInstallStateInput,
    updateAdminServerSuspensionInput,
    useAdminServerTransferProgress,
    useReinstallAdminServer,
    useToggleAdminServerInstallState,
    useUpdateAdminServerSuspension,
} from '@/api/admin/servers/queries';
import { useAdminNodes } from '@/api/admin/nodes/queries';
import { useServerDetail } from '@/components/admin/servers/useServerDetail';
import TransferServerModal from '@/components/admin/servers/TransferServerModal';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { Alert, type AlertType } from '@/components/elements/alert';
import { isObject } from '@/lib/objects';
import { canReinstallServer, SKIPPED_INSTALL_SCRIPT_MESSAGE } from '@/lib/serverStatus';

interface Props {
    server: AdminServer;
}

const descriptionClass = 'text-sm leading-relaxed text-card-foreground';
const actionButtonClass =
    'whitespace-nowrap disabled:border-border disabled:bg-popover disabled:text-muted-foreground disabled:opacity-100';

type CardAlert = { type: AlertType; title: string; message: string };

const ManageActionCard = ({
    title,
    alert,
    description,
    action,
}: {
    title: string;
    alert?: CardAlert;
    description: React.ReactNode;
    action: React.ReactNode;
}) => (
    <TitledGreyBox title={title} className='flex h-full flex-col' contentClassName='flex flex-1 flex-col p-0'>
        <div className='flex flex-1 flex-col gap-3 p-3'>
            {alert && (
                <Alert type={alert.type}>
                    <strong className='font-semibold'>{alert.title}</strong> {alert.message}
                </Alert>
            )}
            <p className={descriptionClass}>{description}</p>
        </div>
        <div className='flex items-center justify-end border-t border-border p-3'>{action}</div>
    </TitledGreyBox>
);

// The span is what receives the hover: a disabled button does not emit pointer events.
const ActionTrigger = ({ reason, children }: { reason?: string; children: React.ReactElement }) => (
    <Tooltip content={reason} disabled={!reason}>
        <span className='inline-flex'>{children}</span>
    </Tooltip>
);

const reinstallBlockedReasonFor = (attributes: AdminServer['attributes']): string | undefined => {
    if (attributes.container.installed !== 1) {
        return 'This action is disabled until the server is installed.';
    }

    if (canReinstallServer(attributes.status, attributes.container.skip_scripts)) {
        return undefined;
    }

    return SKIPPED_INSTALL_SCRIPT_MESSAGE;
};

function ServerManageContent({ server }: Props) {
    const { attributes } = server;
    const reinstallServer = useReinstallAdminServer();
    const toggleInstallState = useToggleAdminServerInstallState();
    const updateSuspension = useUpdateAdminServerSuspension();
    const { data: transfer } = useAdminServerTransferProgress(attributes.id);
    const { data: nodes } = useAdminNodes({ page: 1 });

    const mutationOptions = (close: () => void) => ({ onSettled: close });

    const loading = reinstallServer.isPending || toggleInstallState.isPending || updateSuspension.isPending;
    const activeTransfer = isObject(transfer);
    const canTransfer = (nodes?.meta.pagination.total ?? 0) > 1;
    const reinstallBlockedReason = reinstallBlockedReasonFor(attributes);

    if (attributes.status === 'install_failed') {
        return (
            <TitledGreyBox title='Server Cannot Be Managed'>
                <p className={descriptionClass}>
                    This server is in a failed install state and cannot be recovered. Delete and re-create the server.
                </p>
            </TitledGreyBox>
        );
    }

    return (
        <div className='grid grid-cols-1 gap-6 lg:grid-cols-2'>
            <ManageActionCard
                title='Reinstall Server'
                alert={{ type: 'danger', title: 'Danger!', message: 'This could overwrite server data.' }}
                description='This will reinstall the server with the assigned service scripts.'
                action={
                    <Dialog.ConfirmTrigger
                        title='Reinstall Server'
                        confirm='Reinstall'
                        pending={reinstallServer.isPending}
                        trigger={({ onClick }) => (
                            <ActionTrigger reason={reinstallBlockedReason}>
                                <Button
                                    color='red'
                                    className={actionButtonClass}
                                    disabled={loading || reinstallBlockedReason !== undefined}
                                    onClick={onClick}
                                >
                                    Reinstall Server
                                </Button>
                            </ActionTrigger>
                        )}
                        onConfirmed={(_event, close) =>
                            reinstallServer.mutate(reinstallAdminServerInput(attributes.id), mutationOptions(close))
                        }
                    >
                        This will reinstall the server with its assigned service scripts.{' '}
                        <strong>This could overwrite server data.</strong>
                    </Dialog.ConfirmTrigger>
                }
            />

            <ManageActionCard
                title='Install Status'
                description='If you need to change the install status from uninstalled to installed, or vice versa, you may do so with the button below.'
                action={
                    <Dialog.ConfirmTrigger
                        title='Toggle Install Status'
                        confirm='Toggle'
                        pending={toggleInstallState.isPending}
                        trigger={({ onClick }) => (
                            <Button className={actionButtonClass} disabled={loading} onClick={onClick}>
                                Toggle Install Status
                            </Button>
                        )}
                        onConfirmed={(_event, close) =>
                            toggleInstallState.mutate(
                                toggleAdminServerInstallStateInput(attributes.id),
                                mutationOptions(close)
                            )
                        }
                    >
                        This will flip the install status between installed and installing for this server.
                    </Dialog.ConfirmTrigger>
                }
            />

            {attributes.suspended ? (
                <ManageActionCard
                    title='Unsuspend Server'
                    description='This will unsuspend the server and restore normal user access.'
                    action={
                        <Dialog.ConfirmTrigger
                            title='Unsuspend Server'
                            confirm='Unsuspend'
                            pending={updateSuspension.isPending}
                            trigger={({ onClick }) => (
                                <Button
                                    color='green'
                                    className={actionButtonClass}
                                    disabled={loading}
                                    onClick={onClick}
                                >
                                    Unsuspend Server
                                </Button>
                            )}
                            onConfirmed={(_event, close) =>
                                updateSuspension.mutate(
                                    updateAdminServerSuspensionInput(attributes.id, false),
                                    mutationOptions(close)
                                )
                            }
                        >
                            This will unsuspend the server and restore normal user access.
                        </Dialog.ConfirmTrigger>
                    }
                />
            ) : (
                <ManageActionCard
                    title='Suspend Server'
                    description='This will suspend the server, stop any running processes, and immediately block the user from accessing their files or managing the server.'
                    action={
                        <Dialog.ConfirmTrigger
                            title='Suspend Server'
                            confirm='Suspend'
                            pending={updateSuspension.isPending}
                            trigger={({ onClick }) => (
                                <ActionTrigger
                                    reason={
                                        activeTransfer
                                            ? 'This server is being transferred and cannot be suspended.'
                                            : undefined
                                    }
                                >
                                    <Button
                                        color='orange'
                                        className={actionButtonClass}
                                        disabled={loading || activeTransfer}
                                        onClick={onClick}
                                    >
                                        Suspend Server
                                    </Button>
                                </ActionTrigger>
                            )}
                            onConfirmed={(_event, close) =>
                                updateSuspension.mutate(
                                    updateAdminServerSuspensionInput(attributes.id, true),
                                    mutationOptions(close)
                                )
                            }
                        >
                            This will suspend the server, stop any running processes, and block the user from accessing
                            their files or managing the server.
                        </Dialog.ConfirmTrigger>
                    }
                />
            )}

            <ManageActionCard
                title='Transfer Server'
                alert={
                    activeTransfer
                        ? undefined
                        : { type: 'warning', title: 'Warning!', message: 'This feature may have bugs.' }
                }
                description={
                    activeTransfer
                        ? 'This server is currently being transferred to another node.'
                        : 'Transfer this server to another node connected to this panel.'
                }
                action={
                    <Dialog.Trigger
                        trigger={({ onClick }) => (
                            <ActionTrigger
                                reason={
                                    !activeTransfer && !canTransfer
                                        ? 'Transferring a server requires more than one node to be configured on this panel.'
                                        : undefined
                                }
                            >
                                <Button
                                    color='green'
                                    className={actionButtonClass}
                                    disabled={loading || activeTransfer || !canTransfer}
                                    onClick={onClick}
                                >
                                    Transfer Server
                                </Button>
                            </ActionTrigger>
                        )}
                    >
                        {({ open, onClose }) => <TransferServerModal server={server} open={open} onClose={onClose} />}
                    </Dialog.Trigger>
                }
            />
        </div>
    );
}

export default function ServerManageTab() {
    const { server } = useServerDetail();

    return <ServerManageContent server={server} />;
}
