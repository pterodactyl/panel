import { useMemo } from 'react';
import { KeySquare } from 'lucide-react';
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
    Table,
    Translate,
    useTranslation,
    type SshKeyItem,
    type SshKeyListModel,
    type TableColumn,
    type Translation,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

function DeleteKeyButton({ item, remove }: { item: SshKeyItem; remove: SshKeyListModel['remove'] }) {
    const { t } = useTranslation();

    return (
        <Dialog.ConfirmTrigger
            title={t('Delete SSH Key')}
            confirm={t('Delete Key')}
            onConfirmed={(_event, close) => remove(item.fingerprint).then(close)}
            trigger={({ onClick }) => (
                <DeleteAction aria-label={t('Delete {{name}}', { name: item.name })} onClick={onClick} />
            )}
        >
            <Translate
                i18nKey='Removing the <code>{{name}}</code> SSH key will invalidate its usage across the Panel.'
                values={{ name: item.name }}
                components={{ code: <Code>{null}</Code> }}
            />
        </Dialog.ConfirmTrigger>
    );
}

const sshKeyColumns = (t: Translation['t'], remove: SshKeyListModel['remove']): TableColumn<SshKeyItem>[] => [
    {
        id: 'name',
        label: t('Name'),
        sortValue: (key) => key.name,
        cell: (key) => (
            <div className='min-w-40'>
                <p className='truncate font-medium'>{key.name}</p>
                <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground md:hidden'>
                    SHA256:{key.fingerprint}
                </p>
            </div>
        ),
    },
    {
        id: 'fingerprint',
        label: t('Fingerprint'),
        sortValue: (key) => key.fingerprint,
        cell: (key) => <code className='font-mono text-xs text-muted-foreground'>SHA256:{key.fingerprint}</code>,
        headerClassName: 'hidden md:table-cell',
        cellClassName: 'hidden md:table-cell',
    },
    {
        id: 'created_at',
        label: t('Added'),
        sortValue: (key) => key.createdAt,
        cell: (key) => (
            <time dateTime={key.createdAt} className='whitespace-nowrap text-xs text-muted-foreground'>
                {formatDate(key.createdAt, 'lll')}
            </time>
        ),
        headerClassName: 'hidden lg:table-cell w-40',
        cellClassName: 'hidden lg:table-cell w-40',
    },
    {
        id: 'actions',
        label: <span className='sr-only'>{t('Actions')}</span>,
        cell: (key) => (
            <RowActions>
                <DeleteKeyButton item={key} remove={remove} />
            </RowActions>
        ),
        headerClassName: 'w-14 text-right',
        cellClassName: 'w-14 text-right',
    },
];

export default function SshKeyList({ model, className }: ViewProps<'account.sshKeys.list'>) {
    const { remove } = model;
    const { t } = useTranslation();
    const columns = useMemo(() => sshKeyColumns(t, remove), [t, remove]);

    return (
        <ContentBox title={t('SSH Keys')} className={className}>
            {model.loading ? (
                <Spinner size='large' centered />
            ) : (
                <Table
                    rows={model.keys}
                    columns={columns}
                    keyOf={(key) => key.fingerprint}
                    emptyState={
                        <Empty size='compact'>
                            <EmptyHeader>
                                <EmptyMedia variant='icon'>
                                    <KeySquare />
                                </EmptyMedia>
                                <EmptyTitle>{t('No SSH keys')}</EmptyTitle>
                                <EmptyDescription>
                                    {t('Add a public key to sign in to SFTP without a password.')}
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            )}
        </ContentBox>
    );
}
