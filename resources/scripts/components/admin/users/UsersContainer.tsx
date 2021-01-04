import AdminCheckbox from '@/components/admin/AdminCheckbox';
import React, { useContext, useEffect, useState } from 'react';
import getUsers, { Context as UsersContext } from '@/api/admin/users/getUsers';
import AdminTable, { ContentWrapper, Loading, NoItems, Pagination, TableBody, TableHead, TableHeader, TableRow } from '@/components/admin/AdminTable';
import Button from '@/components/elements/Button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

const RowCheckbox = ({ id }: { id: number}) => {
    const isChecked = AdminContext.useStoreState(state => state.nests.selectedNests.indexOf(id) >= 0);
    const appendSelectedUser = AdminContext.useStoreActions(actions => actions.nests.appendSelectedNest);
    const removeSelectedUser = AdminContext.useStoreActions(actions => actions.nests.removeSelectedNest);

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

    const { page, setPage } = useContext(UsersContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: users, error, isValidating } = getUsers();

    useEffect(() => {
        if (!error) {
            clearFlashes('users');
            return;
        }

        clearAndAddHttpError({ error, key: 'users' });
    }, [ error ]);

    const length = users?.items?.length || 0;

    const setSelectedUsers = AdminContext.useStoreActions(actions => actions.nests.setSelectedNests);
    const selectedUserLength = AdminContext.useStoreState(state => state.nests.selectedNests.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedUsers(e.currentTarget.checked ? (users?.items?.map(user => user.id) || []) : []);
    };

    useEffect(() => {
        setSelectedUsers([]);
    }, [ page ]);

    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Users</h2>
                    <p css={tw`text-base text-neutral-400`}>All registered users on the system.</p>
                </div>

                <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`}>
                    New User
                </Button>
            </div>

            <FlashMessageRender byKey={'users'} css={tw`mb-4`}/>

            <AdminTable>
                { users === undefined || (error && isValidating) ?
                    <Loading/>
                    :
                    length < 1 ?
                        <NoItems/>
                        :
                        <ContentWrapper
                            checked={selectedUserLength === (length === 0 ? -1 : length)}
                            onSelectAllClick={onSelectAllClick}
                        >
                            <Pagination data={users} onPageSelect={setPage}>
                                <div css={tw`overflow-x-auto`}>
                                    <table css={tw`w-full table-auto`}>
                                        <TableHead>
                                            <TableHeader name={'ID'}/>
                                            <TableHeader name={'Email'}/>
                                            <TableHeader name={'Username'}/>
                                            <TableHeader name={'Client Name'}/>
                                        </TableHead>

                                        <TableBody>
                                            {
                                                users.items.map(user => (
                                                    <TableRow key={user.id}>
                                                        <td css={tw`pl-6`}>
                                                            <RowCheckbox id={user.id}/>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{user.id}</td>
                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <NavLink to={`${match.url}/${user.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                {user.email}
                                                            </NavLink>
                                                        </td>
                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{user.username}</td>
                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{user.lastName}, {user.firstName}</td>
                                                    </TableRow>
                                                ))
                                            }
                                        </TableBody>
                                    </table>
                                </div>
                            </Pagination>
                        </ContentWrapper>
                }
            </AdminTable>
        </AdminContentBlock>
    );
};

export default () => {
    const [ page, setPage ] = useState<number>(1);

    return (
        <UsersContext.Provider value={{ page, setPage }}>
            <UsersContainer/>
        </UsersContext.Provider>
    );
};
