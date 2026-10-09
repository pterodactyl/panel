import { useMemo } from 'react';
import { getCoreRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import { KeySquare } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import ContentBox from '@/components/elements/ContentBox';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { useSSHKeys } from '@/api/account/ssh-keys/queries';
import CreateSSHKeyForm from '@/components/dashboard/ssh/CreateSSHKeyForm';
import { ServerError } from '@/components/elements/ScreenBlock';
import DataTable from '@/components/elements/table/DataTable';
import { accountSshColumns } from '@/components/dashboard/ssh/AccountSSHTable';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

export default function AccountSSHContainer() {
    const { data, error, refetch } = useSSHKeys();
    const sshKeys = useMemo(() => data?.data ?? [], [data?.data]);
    const table = useReactTable({
        data: sshKeys,
        columns: accountSshColumns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (key) => key.attributes.fingerprint,
    });

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <PageContentBlock title='SSH Keys'>
            <div className='md:flex flex-nowrap my-10'>
                <ContentBox title='Add SSH Key' className='flex-none w-full md:w-1/2'>
                    <CreateSSHKeyForm />
                </ContentBox>
                <ContentBox title='SSH Keys' className='flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8'>
                    {data ? (
                        <DataTable
                            table={table}
                            emptyState={
                                <Empty className={emptyCompactClass}>
                                    <EmptyHeader>
                                        <EmptyMedia variant='icon'>
                                            <KeySquare />
                                        </EmptyMedia>
                                        <EmptyTitle>No SSH keys</EmptyTitle>
                                        <EmptyDescription>
                                            Add a public key to sign in to SFTP without a password.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            }
                        />
                    ) : (
                        <Spinner size='large' centered />
                    )}
                </ContentBox>
            </div>
        </PageContentBlock>
    );
}
