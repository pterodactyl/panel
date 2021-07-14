import React, { useContext, useEffect, useState } from 'react';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import CopyOnClick from '@/components/elements/CopyOnClick';
import getUsers, { Context as UsersContext, Filters } from '@/api/admin/users/getUsers';
import AdminTable, { ContentWrapper, Loading, NoItems, Pagination, TableBody, TableHead, TableHeader } from '@/components/admin/AdminTable';
import Button from '@/components/elements/Button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

const RowCheckbox = ({ id }: { id: number }) => {
    const isChecked = AdminContext.useStoreState(state => state.users.selectedUsers.indexOf(id) >= 0);
    const appendSelectedUser = AdminContext.useStoreActions(actions => actions.users.appendSelectedUser);
    const removeSelectedUser = AdminContext.useStoreActions(actions => actions.users.removeSelectedUser);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedUser(id);
                } else {
                    removeSelectedUser(id);
                }
            }}
        />
    );
};

const UsersContainer = () => {
    const match = useRouteMatch();

    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(UsersContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: users, error, isValidating } = getUsers();

    useEffect(() => {
        if (!error) {
            clearFlashes('users');
            return;
        }

        clearAndAddHttpError({ key: 'users', error });
    }, [ error ]);

    const length = users?.items?.length || 0;

    const setSelectedUsers = AdminContext.useStoreActions(actions => actions.users.setSelectedUsers);
    const selectedUserLength = AdminContext.useStoreState(state => state.users.selectedUsers.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedUsers(e.currentTarget.checked ? (users?.items?.map(user => user.id) || []) : []);
    };

    const onSearch = (query: string): Promise<void> => {
        return new Promise((resolve) => {
            if (query.length < 2) {
                setFilters(null);
            } else {
                setFilters({ username: query });
            }
            return resolve();
        });
    };

    useEffect(() => {
        setSelectedUsers([]);
    }, [ page ]);

    return (
        <AdminContentBlock title={'Users'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Users</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>All registered users on the system.</p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NavLink to={`${match.url}/new`}>
                        <Button type={'button'} size={'large'} css={tw`h-10 px-4 py-0 whitespace-nowrap`}>
                            New User
                        </Button>
                    </NavLink>
                </div>
            </div>

            <FlashMessageRender byKey={'users'} css={tw`mb-4`}/>

            <AdminTable>
                <ContentWrapper
                    checked={selectedUserLength === (length === 0 ? -1 : length)}
                    onSelectAllClick={onSelectAllClick}
                    onSearch={onSearch}
                >
                    <Pagination data={users} onPageSelect={setPage}>
                        <div css={tw`overflow-x-auto`}>
                            <table css={tw`w-full table-auto`}>
                                <TableHead>
                                    <TableHeader name={'ID'} direction={sort === 'id' ? (sortDirection ? 1 : 2) : null} onClick={() => setSort('id')}/>
                                    <TableHeader name={'Name'} direction={sort === 'email' ? (sortDirection ? 1 : 2) : null} onClick={() => setSort('email')}/>
                                    <TableHeader name={'Username'} direction={sort === 'username' ? (sortDirection ? 1 : 2) : null} onClick={() => setSort('username')}/>
                                    <TableHeader name={'Status'}/>
                                    <TableHeader name={'Role'} direction={sort === 'admin_role_id' ? (sortDirection ? 1 : 2) : null} onClick={() => setSort('admin_role_id')}/>
                                </TableHead>

                                <TableBody>
                                    { users !== undefined && !error && !isValidating && length > 0 &&
                                        users.items.map(user => (
                                            <tr key={user.id} css={tw`h-14 hover:bg-neutral-600`}>
                                                <td css={tw`pl-6`}>
                                                    <RowCheckbox id={user.id}/>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={user.id.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{user.id}</code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 whitespace-nowrap`}>
                                                    <NavLink to={`${match.url}/${user.id}`}>
                                                        <div css={tw`flex items-center`}>
                                                            <div css={tw`flex-shrink-0 h-10 w-10`}>
                                                                <img css={tw`h-10 w-10 rounded-full`} alt="" src={user.avatarURL + '?s=40'}/>
                                                            </div>

                                                            <div css={tw`ml-4`}>
                                                                <div css={tw`text-sm text-neutral-200`}>
                                                                    {user.firstName} {user.lastName}
                                                                </div>

                                                                <div css={tw`text-sm text-neutral-400`}>
                                                                    {user.email}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </NavLink>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{user.username}</td>

                                                <td css={tw`px-6 whitespace-nowrap`}>
                                                    <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}>
                                                        Active
                                                    </span>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{user.roleName || 'None'}</td>
                                            </tr>
                                        ))
                                    }
                                </TableBody>
                            </table>

                            { users === undefined || (error && isValidating) ?
                                <Loading/>
                                :
                                length < 1 ?
                                    <NoItems/>
                                    :
                                    null
                            }
                        </div>
                    </Pagination>
                </ContentWrapper>
            </AdminTable>
        </AdminContentBlock>
    );
};

export default () => {
    const [ page, setPage ] = useState<number>(1);
    const [ filters, setFilters ] = useState<Filters | null>(null);
    const [ sort, setSortState ] = useState<string | null>(null);
    const [ sortDirection, setSortDirection ] = useState<boolean>(false);

    const setSort = (newSort: string | null) => {
        if (sort === newSort) {
            setSortDirection(!sortDirection);
        } else {
            setSortState(newSort);
            setSortDirection(false);
        }
    };

    return (
        <UsersContext.Provider value={{ page, setPage, filters, setFilters, sort, setSort, sortDirection, setSortDirection }}>
            <UsersContainer/>
        </UsersContext.Provider>
    );
};
