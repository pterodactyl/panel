import type { ChangeEvent } from 'react';
import { useContext, useEffect } from 'react';
import { NavLink } from 'react-router-dom';
import tw from 'twin.macro';

import type { Filters } from '@/api/admin/nodes/allocations/getAllocations';
import getAllocations, { Context as AllocationsContext } from '@/api/admin/nodes/allocations/getAllocations';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, {
    ContentWrapper,
    Loading,
    NoItems,
    Pagination,
    TableBody,
    TableHead,
    TableHeader,
    useTableHooks,
} from '@/components/admin/AdminTable';
import DeleteAllocationButton from '@/components/admin/nodes/allocations/DeleteAllocationButton';
import CopyOnClick from '@/components/elements/CopyOnClick';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';

function RowCheckbox({ id }: { id: number }) {
    const isChecked = AdminContext.useStoreState(state => state.allocations.selectedAllocations.indexOf(id) >= 0);
    const appendSelectedAllocation = AdminContext.useStoreActions(
        actions => actions.allocations.appendSelectedAllocation,
    );
    const removeSelectedAllocation = AdminContext.useStoreActions(
        actions => actions.allocations.removeSelectedAllocation,
    );

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedAllocation(id);
                } else {
                    removeSelectedAllocation(id);
                }
            }}
        />
    );
}

interface Props {
    nodeId: number;
    filters?: Filters;
}

function AllocationsTable({ nodeId, filters }: Props) {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(AllocationsContext);
    const { data: allocations, error, isValidating, mutate } = getAllocations(nodeId, ['server']);

    const length = allocations?.items?.length || 0;

    const setSelectedAllocations = AdminContext.useStoreActions(actions => actions.allocations.setSelectedAllocations);
    const selectedAllocationLength = AdminContext.useStoreState(state => state.allocations.selectedAllocations.length);

    const onSelectAllClick = (e: ChangeEvent<HTMLInputElement>) => {
        setSelectedAllocations(
            e.currentTarget.checked ? allocations?.items?.map?.(allocation => allocation.id) || [] : [],
        );
    };

    const onSearch = (query: string): Promise<void> => {
        return new Promise(resolve => {
            if (query.length < 2) {
                setFilters(filters || null);
            } else {
                setFilters({ ...filters, ip: query });
            }
            return resolve();
        });
    };

    useEffect(() => {
        setSelectedAllocations([]);
    }, [page]);

    useEffect(() => {
        if (!error) {
            clearFlashes('allocations');
            return;
        }

        clearAndAddHttpError({ key: 'allocations', error });
    }, [error]);

    return (
        <AdminTable>
            <ContentWrapper
                checked={selectedAllocationLength === (length === 0 ? -1 : length)}
                onSelectAllClick={onSelectAllClick}
                onSearch={onSearch}
            >
                <Pagination data={allocations} onPageSelect={setPage}>
                    <div css={tw`overflow-x-auto`}>
                        <table css={tw`w-full table-auto`}>
                            <TableHead>
                                <TableHeader
                                    name={'IP Address'}
                                    direction={sort === 'ip' ? (sortDirection ? 1 : 2) : null}
                                    onClick={() => setSort('ip')}
                                />
                                <TableHeader name={'Alias'} />
                                <TableHeader
                                    name={'Port'}
                                    direction={sort === 'port' ? (sortDirection ? 1 : 2) : null}
                                    onClick={() => setSort('port')}
                                />
                                <TableHeader name={'Assigned To'} />
                                <TableHeader />
                            </TableHead>

                            <TableBody>
                                {allocations !== undefined &&
                                    !error &&
                                    !isValidating &&
                                    length > 0 &&
                                    allocations.items.map(allocation => (
                                        <tr key={allocation.id} css={tw`h-10 hover:bg-neutral-600`}>
                                            <td css={tw`pl-6`}>
                                                <RowCheckbox id={allocation.id} />
                                            </td>

                                            <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                <CopyOnClick text={allocation.ip}>
                                                    <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                        {allocation.ip}
                                                    </code>
                                                </CopyOnClick>
                                            </td>

                                            {allocation.alias !== null ? (
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={allocation.alias}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {allocation.alias}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>
                                            ) : (
                                                <td />
                                            )}

                                            <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                <CopyOnClick text={allocation.port}>
                                                    <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                        {allocation.port}
                                                    </code>
                                                </CopyOnClick>
                                            </td>

                                            {allocation.relations.server !== undefined ? (
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/servers/${allocation.serverId}`}
                                                        css={tw`text-primary-400 hover:text-primary-300`}
                                                    >
                                                        {allocation.relations.server.name}
                                                    </NavLink>
                                                </td>
                                            ) : (
                                                <td />
                                            )}

                                            <td>
                                                <DeleteAllocationButton
                                                    nodeId={nodeId}
                                                    allocationId={allocation.id}
                                                    onDeleted={async () => {
                                                        await mutate(allocations => ({
                                                            pagination: allocations!.pagination,
                                                            items: allocations!.items.filter(
                                                                a => a.id === allocation.id,
                                                            ),
                                                        }));

                                                        // Go back a page if no more items will exist on the current page.
                                                        if (allocations?.items.length - (1 % 10) === 0) {
                                                            setPage(p => p - 1);
                                                        }
                                                    }}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                            </TableBody>
                        </table>

                        {allocations === undefined || (error && isValidating) ? (
                            <Loading />
                        ) : length < 1 ? (
                            <NoItems />
                        ) : null}
                    </div>
                </Pagination>
            </ContentWrapper>
        </AdminTable>
    );
}

export default (props: Props) => {
    const hooks = useTableHooks<Filters>(props.filters);

    return (
        <AllocationsContext.Provider value={hooks}>
            <AllocationsTable {...props} />
        </AllocationsContext.Provider>
    );
};
