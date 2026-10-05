import { cn } from '@/lib/cn';
import { useNavigate } from '@tanstack/react-router';
import { deleteAdminNodeInput, type NodeUtilization } from '@/api/admin/nodes/queries';
import { useAdminVersion } from '@/api/admin/version/queries';
import { useAdminNodeSystemInformation, useAdminNodeUtilization, useDeleteAdminNode } from '@/api/admin/nodes/queries';
import { useNodeDetail } from '@/components/admin/nodes/useNodeDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Code from '@/components/elements/Code';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';

const UsageBox = ({ title, metric }: { title: string; metric: NodeUtilization['memory'] | undefined }) => {
    const percent = metric ? Math.min(metric.percent, 100) : 0;
    const colour = !metric
        ? 'bg-popover'
        : metric.css === 'red'
          ? 'bg-destructive'
          : metric.css === 'yellow'
            ? 'bg-warning'
            : 'bg-success';

    return (
        <TitledGreyBox title={title}>
            <p className={'text-sm text-foreground'}>
                {metric ? `${metric.value} / ${metric.max} MiB` : <Spinner size={'small'} />}
            </p>
            <div className={'mt-2 h-2 w-full rounded-sm bg-sunken overflow-hidden'}>
                <div
                    className={cn('h-2 rounded-sm transition-[width,background-color] duration-150', colour)}
                    style={{ width: `${percent}%` }}
                />
            </div>
        </TitledGreyBox>
    );
};

export default function NodeAboutTab() {
    const { node } = useNodeDetail();
    const { attributes } = node;
    const navigate = useNavigate();
    const { data: version } = useAdminVersion();
    const deleteNode = useDeleteAdminNode();

    const { data: utilization } = useAdminNodeUtilization(attributes.id);

    const { data: info, error: infoError } = useAdminNodeSystemInformation(attributes.id, {
        refetchInterval: 10000,
    });

    const canDelete = attributes.servers_count === 0;
    const onDelete = (close: () => void) => {
        deleteNode
            .mutateAsync(deleteAdminNodeInput(attributes.id, attributes.name))
            .then(() => navigate({ to: '/panel/nodes' }))
            .catch(close);
    };

    return (
        <div className={'grid grid-cols-1 lg:grid-cols-3 gap-6'}>
            <div className={'lg:col-span-2 space-y-6'}>
                <TitledGreyBox title={'Information'}>
                    <div className={'text-sm text-foreground space-y-3'}>
                        <div className={'flex justify-between items-center'}>
                            <span className={'text-muted-foreground'}>Daemon Version</span>
                            <span>
                                {infoError ? (
                                    <span className={'text-destructive'}>Unavailable</span>
                                ) : info ? (
                                    <Code>{info.version}</Code>
                                ) : (
                                    <Spinner size={'small'} />
                                )}
                                {version && (
                                    <span className={'text-xs text-muted-foreground ml-2'}>
                                        Latest: {version.daemon}
                                    </span>
                                )}
                            </span>
                        </div>
                        <div className={'flex justify-between items-center'}>
                            <span className={'text-muted-foreground'}>System Information</span>
                            <span className={'text-right'}>
                                {infoError ? (
                                    <span className={'text-destructive'}>Unavailable</span>
                                ) : info ? (
                                    `${info.os} (${info.architecture}) ${info.kernel_version}`
                                ) : (
                                    <Spinner size={'small'} />
                                )}
                            </span>
                        </div>
                        <div className={'flex justify-between items-center'}>
                            <span className={'text-muted-foreground'}>Total CPU Threads</span>
                            <span>
                                {infoError ? (
                                    <span className={'text-destructive'}>Unavailable</span>
                                ) : info ? (
                                    info.cpu_count
                                ) : (
                                    <Spinner size={'small'} />
                                )}
                            </span>
                        </div>
                    </div>
                </TitledGreyBox>
                {attributes.description && (
                    <TitledGreyBox title={'Description'}>
                        <pre className={'text-sm text-foreground whitespace-pre-wrap wrap-break-word'}>
                            {attributes.description}
                        </pre>
                    </TitledGreyBox>
                )}
                <TitledGreyBox title={'Delete Node'}>
                    <p className={'text-sm text-muted-foreground'}>
                        Deleting a node is irreversible and immediately removes it from the Panel. There must be no
                        servers associated with this node to continue.
                    </p>
                    {!canDelete && (
                        <p className={'mt-3 text-sm text-warning'}>
                            Move or delete all servers on this node before deleting it.
                        </p>
                    )}
                    <div className={'mt-4 text-right'}>
                        <Dialog.ConfirmTrigger
                            title={'Delete node'}
                            confirm={'Delete Node'}
                            pending={deleteNode.isPending}
                            onConfirmed={(_event, close) => onDelete(close)}
                            trigger={({ onClick }) => (
                                <Button
                                    type={'button'}
                                    color={'red'}
                                    disabled={!canDelete || deleteNode.isPending}
                                    onClick={onClick}
                                >
                                    Delete Node
                                </Button>
                            )}
                        >
                            Deleting <strong>{attributes.name}</strong> is permanent and cannot be undone.
                        </Dialog.ConfirmTrigger>
                    </div>
                </TitledGreyBox>
            </div>
            <div className={'space-y-6'}>
                {attributes.maintenance_mode && (
                    <TitledGreyBox title={'Maintenance'}>
                        <p className={'text-sm text-warning'}>This node is currently under maintenance.</p>
                    </TitledGreyBox>
                )}
                <UsageBox title={'Disk Space Allocated'} metric={utilization?.disk} />
                <UsageBox title={'Memory Allocated'} metric={utilization?.memory} />
                <TitledGreyBox title={'Total Servers'}>
                    <p className={'text-2xl text-foreground font-medium'}>{attributes.servers_count}</p>
                    <p className={'text-sm text-muted-foreground'}>See the Servers tab for the full server list.</p>
                </TitledGreyBox>
            </div>
        </div>
    );
}
