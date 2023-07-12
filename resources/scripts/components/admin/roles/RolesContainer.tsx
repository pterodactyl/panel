import type { ChangeEvent } from 'react';
import { useContext, useEffect } from 'react';
import { NavLink } from 'react-router-dom';
import tw from 'twin.macro';

import type { Filters } from '@/api/admin/roles';
import { getRoles, Context as RolesContext } from '@/api/admin/roles';
import { AdminContext } from '@/state/admin';
import NewRoleButton from '@/components/admin/roles/NewRoleButton';
import FlashMessageRender from '@/components/FlashMessageRender';
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
import useFlash from '@/plugins/useFlash';

const RowCheckbox = ({ id }: { id: number }) => {
    const isChecked = AdminContext.useStoreState(state => state.roles.selectedRoles.indexOf(id) >= 0);
    const appendSelectedRole = AdminContext.useStoreActions(actions => actions.roles.appendSelectedRole);
    const removeSelectedRole = AdminContext.useStoreActions(actions => actions.roles.removeSelectedRole);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedRole(id);
                } else {
                    removeSelectedRole(id);
                }
            }}
        />
    );
};

const RolesContainer = () => {
    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(RolesContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: roles, error, isValidating } = getRoles();

    useEffect(() => {
        if (!error) {
            clearFlashes('roles');
            return;
        }

        clearAndAddHttpError({ key: 'roles', error });
    }, [error]);

    const length = roles?.items?.length || 0;

    const setSelectedRoles = AdminContext.useStoreActions(actions => actions.roles.setSelectedRoles);
    const selectedRolesLength = AdminContext.useStoreState(state => state.roles.selectedRoles.length);

    const onSelectAllClick = (e: ChangeEvent<HTMLInputElement>) => {
        setSelectedRoles(e.currentTarget.checked ? roles?.items?.map(role => role.id) || [] : []);
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
        setSelectedRoles([]);
    }, [page]);

    return (
        <AdminContentBlock title={'Roles'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Roles</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        Soon&trade;
                    </p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NewRoleButton />
                </div>
            </div>

            <FlashMessageRender byKey={'roles'} css={tw`mb-4`} />

            <AdminTable>
                <ContentWrapper
                    checked={selectedRolesLength === (length === 0 ? -1 : length)}
                    onSelectAllClick={onSelectAllClick}
                    onSearch={onSearch}
                >
                    <Pagination data={roles} onPageSelect={setPage}>
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
                                    {roles !== undefined &&
                                        !error &&
                                        !isValidating &&
                                        length > 0 &&
                                        roles.items.map(role => (
                                            <TableRow key={role.id}>
                                                <td css={tw`pl-6`}>
                                                    <RowCheckbox id={role.id} />
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={role.id.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {role.id}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/roles/${role.id}`}
                                                        css={tw`text-primary-400 hover:text-primary-300`}
                                                    >
                                                        {role.name}
                                                    </NavLink>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    {role.description}
                                                </td>
                                            </TableRow>
                                        ))}
                                </TableBody>
                            </table>

                            {roles === undefined || (error && isValidating) ? (
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
        <RolesContext.Provider value={hooks}>
            <RolesContainer />
        </RolesContext.Provider>
    );
};
