import type { ChangeEvent } from 'react';
import { useContext, useEffect } from 'react';
import { NavLink } from 'react-router-dom';
import tw from 'twin.macro';

import type { Filters } from '@/api/admin/mounts/getMounts';
import getMounts, { Context as MountsContext } from '@/api/admin/mounts/getMounts';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminTable, {
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
    Pagination,
    Loading,
    NoItems,
    ContentWrapper,
    useTableHooks,
} from '@/components/admin/AdminTable';
import { Button } from '@/components/elements/button';
import { Size } from '@/components/elements/button/types';
import CopyOnClick from '@/components/elements/CopyOnClick';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';

const RowCheckbox = ({ id }: { id: number }) => {
    const isChecked = AdminContext.useStoreState(state => state.mounts.selectedMounts.indexOf(id) >= 0);
    const appendSelectedMount = AdminContext.useStoreActions(actions => actions.mounts.appendSelectedMount);
    const removeSelectedMount = AdminContext.useStoreActions(actions => actions.mounts.removeSelectedMount);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedMount(id);
                } else {
                    removeSelectedMount(id);
                }
            }}
        />
    );
};

const MountsContainer = () => {
    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(MountsContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: mounts, error, isValidating } = getMounts();

    useEffect(() => {
        if (!error) {
            clearFlashes('mounts');
            return;
        }

        clearAndAddHttpError({ key: 'mounts', error });
    }, [error]);

    const length = mounts?.items?.length || 0;

    const setSelectedMounts = AdminContext.useStoreActions(actions => actions.mounts.setSelectedMounts);
    const selectedMountsLength = AdminContext.useStoreState(state => state.mounts.selectedMounts.length);

    const onSelectAllClick = (e: ChangeEvent<HTMLInputElement>) => {
        setSelectedMounts(e.currentTarget.checked ? mounts?.items?.map(mount => mount.id) || [] : []);
    };

    const onSearch = (query: string): Promise<void> => {
        return new Promise(resolve => {
            if (query.length < 2) {
                setFilters(null);
            } else {
                setFilters({ name: query });
            }
            return resolve();
        });
    };

    useEffect(() => {
        setSelectedMounts([]);
    }, [page]);

    return (
        <AdminContentBlock title={'Mounts'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Mounts</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        Configure and manage additional mount points for servers.
                    </p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NavLink to={`/admin/mounts/new`}>
                        <Button type={'button'} size={Size.Large} css={tw`h-10 px-4 py-0 whitespace-nowrap`}>
                            New Mount
                        </Button>
                    </NavLink>
                </div>
            </div>

            <FlashMessageRender byKey={'mounts'} css={tw`mb-4`} />

            <AdminTable>
                <ContentWrapper
                    checked={selectedMountsLength === (length === 0 ? -1 : length)}
                    onSelectAllClick={onSelectAllClick}
                    onSearch={onSearch}
                >
                    <Pagination data={mounts} onPageSelect={setPage}>
                        <div css={tw`overflow-x-auto`}>
                            <table css={tw`w-full table-auto`}>
                                <TableHead>
                                    <TableHeader
                                        name={'ID'}
                                        direction={sort === 'id' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('id')}
                                    />
                                    <TableHeader
                                        name={'Name'}
                                        direction={sort === 'name' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('name')}
                                    />
                                    <TableHeader
                                        name={'Source Path'}
                                        direction={sort === 'source' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('source')}
                                    />
                                    <TableHeader
                                        name={'Target Path'}
                                        direction={sort === 'target' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('target')}
                                    />
                                    <th css={tw`px-6 py-2`} />
                                    <th css={tw`px-6 py-2`} />
                                </TableHead>

                                <TableBody>
                                    {mounts !== undefined &&
                                        !error &&
                                        !isValidating &&
                                        length > 0 &&
                                        mounts.items.map(mount => (
                                            <TableRow key={mount.id}>
                                                <td css={tw`pl-6`}>
                                                    <RowCheckbox id={mount.id} />
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={mount.id.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {mount.id}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/mounts/${mount.id}`}
                                                        css={tw`text-primary-400 hover:text-primary-300`}
                                                    >
                                                        {mount.name}
                                                    </NavLink>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={mount.source.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {mount.source}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={mount.target.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {mount.target}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 whitespace-nowrap`}>
                                                    {mount.readOnly ? (
                                                        <span
                                                            css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}
                                                        >
                                                            Read Only
                                                        </span>
                                                    ) : (
                                                        <span
                                                            css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-yellow-200 text-yellow-800`}
                                                        >
                                                            Writable
                                                        </span>
                                                    )}
                                                </td>

                                                <td css={tw`px-6 whitespace-nowrap`}>
                                                    {mount.userMountable ? (
                                                        <span
                                                            css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}
                                                        >
                                                            Mountable
                                                        </span>
                                                    ) : (
                                                        <span
                                                            css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-yellow-200 text-yellow-800`}
                                                        >
                                                            Admin Only
                                                        </span>
                                                    )}
                                                </td>
                                            </TableRow>
                                        ))}
                                </TableBody>
                            </table>

                            {mounts === undefined || (error && isValidating) ? (
                                <Loading />
                            ) : length < 1 ? (
                                <NoItems />
                            ) : null}
                        </div>
                    </Pagination>
                </ContentWrapper>
            </AdminTable>
        </AdminContentBlock>
    );
};

export default () => {
    const hooks = useTableHooks<Filters>();

    return (
        <MountsContext.Provider value={hooks}>
            <MountsContainer />
        </MountsContext.Provider>
    );
};
