import type { ChangeEvent } from 'react';
import { useContext, useEffect } from 'react';
import { NavLink, useParams } from 'react-router-dom';
import tw from 'twin.macro';

import type { Filters } from '@/api/admin/nests/getEggs';
import getEggs, { Context as EggsContext } from '@/api/admin/nests/getEggs';
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
import { Context } from '@/components/admin/nests/NestEditContainer';
import CopyOnClick from '@/components/elements/CopyOnClick';
import useFlash from '@/plugins/useFlash';

const RowCheckbox = ({ id }: { id: number }) => {
    const isChecked = Context.useStoreState(state => state.selectedEggs.indexOf(id) >= 0);
    const appendSelectedEggs = Context.useStoreActions(actions => actions.appendSelectedEggs);
    const removeSelectedEggs = Context.useStoreActions(actions => actions.removeSelectedEggs);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedEggs(id);
                } else {
                    removeSelectedEggs(id);
                }
            }}
        />
    );
};

const EggsTable = () => {
    const params = useParams<'nestId' | 'id'>();

    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(EggsContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: eggs, error, isValidating } = getEggs(Number(params.nestId));

    useEffect(() => {
        if (!error) {
            clearFlashes('nests');
            return;
        }

        clearAndAddHttpError({ key: 'nests', error });
    }, [error]);

    const length = eggs?.items?.length || 0;

    const setSelectedEggs = Context.useStoreActions(actions => actions.setSelectedEggs);
    const selectedEggsLength = Context.useStoreState(state => state.selectedEggs.length);

    const onSelectAllClick = (e: ChangeEvent<HTMLInputElement>) => {
        setSelectedEggs(e.currentTarget.checked ? eggs?.items?.map(nest => nest.id) || [] : []);
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
        setSelectedEggs([]);
    }, [page]);

    return (
        <AdminTable>
            <ContentWrapper
                checked={selectedEggsLength === (length === 0 ? -1 : length)}
                onSelectAllClick={onSelectAllClick}
                onSearch={onSearch}
            >
                <Pagination data={eggs} onPageSelect={setPage}>
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
                                {eggs !== undefined &&
                                    !error &&
                                    !isValidating &&
                                    length > 0 &&
                                    eggs.items.map(egg => (
                                        <TableRow key={egg.id}>
                                            <td css={tw`pl-6`}>
                                                <RowCheckbox id={egg.id} />
                                            </td>

                                            <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                <CopyOnClick text={egg.id.toString()}>
                                                    <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                        {egg.id}
                                                    </code>
                                                </CopyOnClick>
                                            </td>

                                            <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                <NavLink
                                                    to={`/admin/nests/${params.nestId}/eggs/${egg.id}`}
                                                    css={tw`text-primary-400 hover:text-primary-300`}
                                                >
                                                    {egg.name}
                                                </NavLink>
                                            </td>

                                            <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                {egg.description}
                                            </td>
                                        </TableRow>
                                    ))}
                            </TableBody>
                        </table>

                        {eggs === undefined || (error && isValidating) ? <Loading /> : length < 1 ? <NoItems /> : null}
                    </div>
                </Pagination>
            </ContentWrapper>
        </AdminTable>
    );
};

export default () => {
    const hooks = useTableHooks<Filters>();

    return (
        <EggsContext.Provider value={hooks}>
            <EggsTable />
        </EggsContext.Provider>
    );
};
