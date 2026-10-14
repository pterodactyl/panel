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
    type ApiKeyItem,
    type TableColumn,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

const apiKeyColumns = (onDelete: (identifier: string) => void): TableColumn<ApiKeyItem>[] => [
    {
        id: 'description',
        label: 'Description',
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
        label: 'Identifier',
        sortValue: (key) => key.identifier,
        cell: (key) => <code className='font-mono text-xs text-muted-foreground'>{key.identifier}</code>,
        headerClassName: 'hidden md:table-cell',
        cellClassName: 'hidden md:table-cell',
    },
    {
        id: 'last_used_at',
        label: 'Last used',
        sortValue: (key) => key.lastUsedAt,
        cell: (key) => (
            <span className='whitespace-nowrap text-xs text-muted-foreground'>
                {key.lastUsedAt ? formatDate(key.lastUsedAt, 'MMM D, YYYY HH:mm') : 'Never'}
            </span>
        ),
        headerClassName: 'hidden lg:table-cell w-40',
        cellClassName: 'hidden lg:table-cell w-40',
    },
    {
        id: 'actions',
        label: <span className='sr-only'>Actions</span>,
        cell: (key) => (
            <RowActions>
                <DeleteAction
                    aria-label={`Delete ${key.description} API key`}
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
    const columns = useMemo(() => apiKeyColumns(setDeleteIdentifier), []);

    return (
        <ContentBox title='API Keys' className={className}>
            <SpinnerOverlay visible={model.deleting} />
            <Dialog.Confirm
                title='Delete API Key'
                confirm='Delete Key'
                open={!!deleteIdentifier}
                pending={model.deleting}
                onClose={() => setDeleteIdentifier('')}
                onConfirmed={() => void model.remove(deleteIdentifier).then(() => setDeleteIdentifier(''))}
            >
                All requests using the <Code>{deleteIdentifier}</Code> key will be invalidated.
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
                                <EmptyTitle>No API keys</EmptyTitle>
                                <EmptyDescription>
                                    Create a key to use the client API from your own scripts and tools.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            )}
        </ContentBox>
    );
}
