import { useMemo, useState } from 'react';
import { KeyRound } from 'lucide-react';
import {
    Code,
    ContentBox,
    DeleteAction,
    Dialog,
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    formatDate,
    RowActions,
    Spinner,
    SpinnerOverlay,
    Table,
    Translate,
    useTranslation,
    type ApiKeyItem,
    type TableColumn,
    type Translation,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

const apiKeyColumns = (t: Translation['t'], onDelete: (identifier: string) => void): TableColumn<ApiKeyItem>[] => [
    {
        id: 'description',
        label: t('Description'),
        sortValue: (key) => key.description,
        cell: (key) => (
            <div className='min-w-40'>
                <p className='truncate font-medium'>{key.description}</p>
                <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground md:hidden'>{key.identifier}</p>
            </div>
        ),
    },
    {
        id: 'identifier',
        label: t('Identifier'),
        sortValue: (key) => key.identifier,
        cell: (key) => <code className='font-mono text-xs text-muted-foreground'>{key.identifier}</code>,
        headerClassName: 'hidden md:table-cell',
        cellClassName: 'hidden md:table-cell',
    },
    {
        id: 'last_used_at',
        label: t('Last used'),
        sortValue: (key) => key.lastUsedAt,
        cell: (key) => (
            <span className='whitespace-nowrap text-xs text-muted-foreground'>
                {key.lastUsedAt ? formatDate(key.lastUsedAt, 'lll') : t('Never')}
            </span>
        ),
        headerClassName: 'hidden lg:table-cell w-40',
        cellClassName: 'hidden lg:table-cell w-40',
    },
    {
        id: 'actions',
        label: <span className='sr-only'>{t('Actions')}</span>,
        cell: (key) => (
            <RowActions>
                <DeleteAction
                    aria-label={t('Delete {{name}} API key', { name: key.description })}
                    onClick={() => onDelete(key.identifier)}
                />
            </RowActions>
        ),
        headerClassName: 'w-14 text-right',
        cellClassName: 'w-14 text-right',
    },
];

export default function ApiKeyList({ model, className }: ViewProps<'account.apiKeys.list'>) {
    const [deleteIdentifier, setDeleteIdentifier] = useState('');
    const { t } = useTranslation();
    const columns = useMemo(() => apiKeyColumns(t, setDeleteIdentifier), [t]);

    return (
        <ContentBox title={t('API Keys')} className={className}>
            <SpinnerOverlay visible={model.deleting} />
            <Dialog.Confirm
                title={t('Delete API Key')}
                confirm={t('Delete Key')}
                open={!!deleteIdentifier}
                pending={model.deleting}
                onClose={() => setDeleteIdentifier('')}
                onConfirmed={() => void model.remove(deleteIdentifier).then(() => setDeleteIdentifier(''))}
            >
                <Translate
                    i18nKey='All requests using the <code>{{identifier}}</code> key will be invalidated.'
                    values={{ identifier: deleteIdentifier }}
                    components={{ code: <Code>{null}</Code> }}
                />
            </Dialog.Confirm>
            {model.loading ? (
                <Spinner size='large' centered />
            ) : (
                <Table
                    rows={model.keys}
                    columns={columns}
                    keyOf={(key) => key.identifier}
                    emptyState={
                        <Empty size='compact'>
                            <EmptyHeader>
                                <EmptyMedia variant='icon'>
                                    <KeyRound />
                                </EmptyMedia>
                                <EmptyTitle>{t('No API keys')}</EmptyTitle>
                                <EmptyDescription>
                                    {t('Create a key to use the client API from your own scripts and tools.')}
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            )}
        </ContentBox>
    );
}
