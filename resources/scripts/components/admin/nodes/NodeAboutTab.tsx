import type { ReactNode } from 'react';
import { bytesRatioToString, mbToBytes } from '@/lib/formatters';
import { useNavigate } from '@tanstack/react-router';
import { deleteAdminNodeInput, type NodeUtilization } from '@/api/admin/nodes/queries';
import { useAdminVersion } from '@/api/admin/version/queries';
import { useAdminNodeSystemInformation, useAdminNodeUtilization, useDeleteAdminNode } from '@/api/admin/nodes/queries';
import { useNodeDetail } from '@/components/admin/nodes/useNodeDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Code from '@/components/elements/Code';
import ProgressBar from '@/components/elements/ProgressBar';
import type { UiTone } from '@/themes/skins';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import Translate from '@/components/elements/Translate';
import { useTranslation } from '@/sdk/localization';

const usageTone = (metric: NodeUtilization['memory'] | undefined): UiTone => {
    if (!metric) {
        return 'neutral';
    }

    if (metric.css === 'red') {
        return 'danger';
    }

    if (metric.css === 'yellow') {
        return 'warning';
    }

    return 'success';
};

type SystemInformation = NonNullable<ReturnType<typeof useAdminNodeSystemInformation>['data']>;

interface SystemInformationValueProps {
    info: SystemInformation | undefined;
    error: unknown;
    children: (info: SystemInformation) => ReactNode;
}

const SystemInformationValue = ({ info, error, children }: SystemInformationValueProps) => {
    const { t } = useTranslation();

    if (error) {
        return <span className='text-destructive'>{t('Unavailable')}</span>;
    }

    if (!info) {
        return <Spinner size='small' />;
    }

    return children(info);
};

const megabytes = (formatted: string): number => Number(formatted.replaceAll(',', ''));

const UsageBox = ({ title, metric }: { title: string; metric: NodeUtilization['memory'] | undefined }) => {
    const { t } = useTranslation();
    const percent = metric ? Math.min(metric.percent, 100) : 0;

    return (
        <TitledGreyBox title={title}>
            <p className='text-sm text-foreground'>
                {metric ? (
                    bytesRatioToString(mbToBytes(megabytes(metric.value)), mbToBytes(megabytes(metric.max)))
                ) : (
                    <Spinner size='small' />
                )}
            </p>
            <ProgressBar
                value={percent}
                tone={usageTone(metric)}
                label={t('{{title}} usage', { title })}
                className='mt-2'
            />
        </TitledGreyBox>
    );
};

export default function NodeAboutTab() {
    const { t } = useTranslation();
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
                <TitledGreyBox title={t('Information')}>
                    <div className='text-sm text-foreground space-y-3'>
                        <div className='flex justify-between items-center'>
                            <span className='text-muted-foreground'>{t('Daemon Version')}</span>
                            <span>
                                <SystemInformationValue info={info} error={infoError}>
                                    {(value) => <Code>{value.version}</Code>}
                                </SystemInformationValue>
                                {version && (
                                    <span className='text-xs text-muted-foreground ml-2'>
                                        {t('Latest: {{version}}', { version: version.daemon })}
                                    </span>
                                )}
                            </span>
                        </div>
                        <div className='flex justify-between items-center'>
                            <span className='text-muted-foreground'>{t('System Information')}</span>
                            <span className='text-right'>
                                <SystemInformationValue info={info} error={infoError}>
                                    {(value) => `${value.os} (${value.architecture}) ${value.kernel_version}`}
                                </SystemInformationValue>
                            </span>
                        </div>
                        <div className='flex justify-between items-center'>
                            <span className='text-muted-foreground'>{t('Total CPU Threads')}</span>
                            <span>
                                <SystemInformationValue info={info} error={infoError}>
                                    {(value) => value.cpu_count}
                                </SystemInformationValue>
                            </span>
                        </div>
                    </div>
                </TitledGreyBox>
                {attributes.description && (
                    <TitledGreyBox title={t('Description')}>
                        <pre className='text-sm text-foreground whitespace-pre-wrap wrap-break-word'>
                            {attributes.description}
                        </pre>
                    </TitledGreyBox>
                )}
                <TitledGreyBox title={t('Delete Node')}>
                    <p className='text-sm text-muted-foreground'>
                        {t(
                            'Deleting a node is irreversible and immediately removes it from the Panel. There must be no servers associated with this node to continue.'
                        )}
                    </p>
                    {!canDelete && (
                        <p className='mt-3 text-sm text-warning'>
                            {t('Move or delete all servers on this node before deleting it.')}
                        </p>
                    )}
                    <div className='mt-4 text-right'>
                        <Dialog.ConfirmTrigger
                            title={t('Delete node')}
                            confirm={t('Delete Node')}
                            pending={deleteNode.isPending}
                            onConfirmed={(_event, close) => onDelete(close)}
                            trigger={({ onClick }) => (
                                <Button
                                    type='button'
                                    color='red'
                                    disabled={!canDelete || deleteNode.isPending}
                                    onClick={onClick}
                                >
                                    {t('Delete Node')}
                                </Button>
                            )}
                        >
                            <Translate
                                i18nKey='Deleting <bold>{{name}}</bold> is permanent and cannot be undone.'
                                values={{ name: attributes.name }}
                                components={{ bold: <strong /> }}
                            />
                        </Dialog.ConfirmTrigger>
                    </div>
                </TitledGreyBox>
            </div>
            <div className='space-y-6'>
                {attributes.maintenance_mode && (
                    <TitledGreyBox title={t('Maintenance')}>
                        <p className='text-sm text-warning'>{t('This node is currently under maintenance.')}</p>
                    </TitledGreyBox>
                )}
                <UsageBox title={t('Disk Space Allocated')} metric={utilization?.disk} />
                <UsageBox title={t('Memory Allocated')} metric={utilization?.memory} />
                <TitledGreyBox title={t('Total Servers')}>
                    <p className='text-2xl text-foreground font-medium'>{attributes.servers_count}</p>
                    <p className='text-sm text-muted-foreground'>
                        {t('See the Servers tab for the full server list.')}
                    </p>
                </TitledGreyBox>
            </div>
        </div>
    );
}
