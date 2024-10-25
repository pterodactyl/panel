import type { ChangeEvent } from 'react';
import { useContext, useEffect } from 'react';
import { NavLink } from 'react-router-dom';
import tw from 'twin.macro';

import type { Filters } from '@/api/admin/nests/getNests';
import getNests, { Context as NestsContext } from '@/api/admin/nests/getNests';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
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
import CopyOnClick from '@/components/elements/CopyOnClick';
import NewNestButton from '@/components/admin/nests/NewNestButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';

const RowCheckbox = ({ id }: { id: number }) => {
    const isChecked = AdminContext.useStoreState(state => state.nests.selectedNests.indexOf(id) >= 0);
    const appendSelectedNest = AdminContext.useStoreActions(actions => actions.nests.appendSelectedNest);
    const removeSelectedNest = AdminContext.useStoreActions(actions => actions.nests.removeSelectedNest);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedNest(id);
                } else {
                    removeSelectedNest(id);
                }
            }}
        />
    );
};

const NestsContainer = () => {
    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(NestsContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: nests, error, isValidating } = getNests();

    useEffect(() => {
        if (!error) {
            clearFlashes('nests');
            return;
        }

        clearAndAddHttpError({ key: 'nests', error });
    }, [error]);

    const length = nests?.items?.length || 0;

    const setSelectedNests = AdminContext.useStoreActions(actions => actions.nests.setSelectedNests);
    const selectedNestsLength = AdminContext.useStoreState(state => state.nests.selectedNests.length);

    const onSelectAllClick = (e: ChangeEvent<HTMLInputElement>) => {
        setSelectedNests(e.currentTarget.checked ? nests?.items?.map(nest => nest.id) || [] : []);
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
        setSelectedNests([]);
    }, [page]);

    return (
        <AdminContentBlock title={'Nests'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Nests</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        All nests currently available on this system.
                    </p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NewNestButton />
                </div>
            </div>

            <FlashMessageRender byKey={'nests'} css={tw`mb-4`} />

            <AdminTable>
                <ContentWrapper
                    checked={selectedNestsLength === (length === 0 ? -1 : length)}
                    onSelectAllClick={onSelectAllClick}
                    onSearch={onSearch}
                >
                    <Pagination data={nests} onPageSelect={setPage}>
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
                                    <TableHeader name={'Description'} />
                                </TableHead>

                                <TableBody>
                                    {nests !== undefined &&
                                        !error &&
                                        !isValidating &&
                                        length > 0 &&
                                        nests.items.map(nest => (
                                            <TableRow key={nest.id}>
                                                <td css={tw`pl-6`}>
                                                    <RowCheckbox id={nest.id} />
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={nest.id.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {nest.id}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/nests/${nest.id}`}
                                                        css={tw`text-primary-400 hover:text-primary-300`}
                                                    >
                                                        {nest.name}
                                                    </NavLink>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    {nest.description}
                                                </td>
                                            </TableRow>
                                        ))}
                                </TableBody>
                            </table>

                            {nests === undefined || (error && isValidating) ? (
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
        <NestsContext.Provider value={hooks}>
            <NestsContainer />
        </NestsContext.Provider>
    );
};
