import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { bytesRatioToString, mbToBytes } from '@/lib/formatters';
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

const usageColour = (metric: NodeUtilization['memory'] | undefined): string => {
    if (!metric) {
        return 'bg-popover';
    }

    if (metric.css === 'red') {
        return 'bg-destructive';
    }

    if (metric.css === 'yellow') {
        return 'bg-warning';
    }

    return 'bg-success';
};

type SystemInformation = NonNullable<ReturnType<typeof useAdminNodeSystemInformation>['data']>;

interface SystemInformationValueProps {
    info: SystemInformation | undefined;
    error: unknown;
    children: (info: SystemInformation) => ReactNode;
}

const SystemInformationValue = ({ info, error, children }: SystemInformationValueProps) => {
    if (error) {
        return <span className='text-destructive'>Unavailable</span>;
    }

    if (!info) {
        return <Spinner size='small' />;
    }

    return children(info);
};

const megabytes = (formatted: string): number => Number(formatted.replaceAll(',', ''));

const UsageBox = ({ title, metric }: { title: string; metric: NodeUtilization['memory'] | undefined }) => {
    const percent = metric ? Math.min(metric.percent, 100) : 0;
    const colour = usageColour(metric);

    return (
        <TitledGreyBox title={title}>
            <p className='text-sm text-foreground'>
                {metric ? (
                    bytesRatioToString(mbToBytes(megabytes(metric.value)), mbToBytes(megabytes(metric.max)))
                ) : (
                    <Spinner size='small' />
                )}
            </p>
            <div className='mt-2 h-2 w-full rounded-sm bg-sunken overflow-hidden'>
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
        <div className='grid grid-cols-1 lg:grid-cols-3 gap-6'>
            <div className='lg:col-span-2 space-y-6'>
                <TitledGreyBox title='Information'>
                    <div className='text-sm text-foreground space-y-3'>
                        <div className='flex justify-between items-center'>
                            <span className='text-muted-foreground'>Daemon Version</span>
                            <span>
                                <SystemInformationValue info={info} error={infoError}>
                                    {(value) => <Code>{value.version}</Code>}
                                </SystemInformationValue>
                                {version && (
                                    <span className='text-xs text-muted-foreground ml-2'>Latest: {version.daemon}</span>
                                )}
                            </span>
                        </div>
                        <div className='flex justify-between items-center'>
                            <span className='text-muted-foreground'>System Information</span>
                            <span className='text-right'>
                                <SystemInformationValue info={info} error={infoError}>
                                    {(value) => `${value.os} (${value.architecture}) ${value.kernel_version}`}
                                </SystemInformationValue>
                            </span>
                        </div>
                        <div className='flex justify-between items-center'>
                            <span className='text-muted-foreground'>Total CPU Threads</span>
                            <span>
                                <SystemInformationValue info={info} error={infoError}>
                                    {(value) => value.cpu_count}
                                </SystemInformationValue>
                            </span>
                        </div>
                    </div>
                </TitledGreyBox>
                {attributes.description && (
                    <TitledGreyBox title='Description'>
                        <pre className='text-sm text-foreground whitespace-pre-wrap wrap-break-word'>
                            {attributes.description}
                        </pre>
                    </TitledGreyBox>
                )}
                <TitledGreyBox title='Delete Node'>
                    <p className='text-sm text-muted-foreground'>
                        Deleting a node is irreversible and immediately removes it from the Panel. There must be no
                        servers associated with this node to continue.
                    </p>
                    {!canDelete && (
                        <p className='mt-3 text-sm text-warning'>
                            Move or delete all servers on this node before deleting it.
                        </p>
                    )}
                    <div className='mt-4 text-right'>
                        <Dialog.ConfirmTrigger
                            title='Delete node'
                            confirm='Delete Node'
                            pending={deleteNode.isPending}
                            onConfirmed={(_event, close) => onDelete(close)}
                            trigger={({ onClick }) => (
                                <Button
                                    type='button'
                                    color='red'
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
            <div className='space-y-6'>
                {attributes.maintenance_mode && (
                    <TitledGreyBox title='Maintenance'>
                        <p className='text-sm text-warning'>This node is currently under maintenance.</p>
                    </TitledGreyBox>
                )}
                <UsageBox title='Disk Space Allocated' metric={utilization?.disk} />
                <UsageBox title='Memory Allocated' metric={utilization?.memory} />
                <TitledGreyBox title='Total Servers'>
                    <p className='text-2xl text-foreground font-medium'>{attributes.servers_count}</p>
                    <p className='text-sm text-muted-foreground'>See the Servers tab for the full server list.</p>
                </TitledGreyBox>
            </div>
        </div>
    );
}
