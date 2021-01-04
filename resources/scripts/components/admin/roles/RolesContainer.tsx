import React, { useEffect, useState } from 'react';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import { AdminContext } from '@/state/admin';
import { httpErrorToHuman } from '@/api/http';
import NewRoleButton from '@/components/admin/roles/NewRoleButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import getRoles from '@/api/admin/roles/getRoles';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, { ContentWrapper, Loading, NoItems, TableBody, TableHead, TableHeader, TableRow } from '@/components/admin/AdminTable';

const RowCheckbox = ({ id }: { id: number}) => {
    const isChecked = AdminContext.useStoreState(state => state.roles.selectedRoles.indexOf(id) >= 0);
    const appendSelectedRole = AdminContext.useStoreActions(actions => actions.roles.appendSelectedRole);
    const removeSelectedRole = AdminContext.useStoreActions(actions => actions.roles.removeSelectedRole);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedRole(id);
                } else {
                    removeSelectedRole(id);
                }
            }}
        />
    );
};

export default () => {
    const match = useRouteMatch();

    const { addError, clearFlashes } = useFlash();
    const [ loading, setLoading ] = useState(true);

    const roles = useDeepMemoize(AdminContext.useStoreState(state => state.roles.data));
    const setRoles = AdminContext.useStoreActions(state => state.roles.setRoles);

    const setSelectedRoles = AdminContext.useStoreActions(actions => actions.roles.setSelectedRoles);
    const selectedRolesLength = AdminContext.useStoreState(state => state.roles.selectedRoles.length);

    useEffect(() => {
        setLoading(!roles.length);
        clearFlashes('roles');

        getRoles()
            .then(roles => setRoles(roles))
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'roles' });
            })
            .then(() => setLoading(false));
    }, []);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedRoles(e.currentTarget.checked ? (roles.map(role => role.id) || []) : []);
    };

    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Roles</h2>
                    <p css={tw`text-base text-neutral-400`}>Soon&trade;</p>
                </div>

                <NewRoleButton/>
            </div>

            <FlashMessageRender byKey={'roles'} css={tw`mb-4`}/>

            <AdminTable>
                { loading ?
                    <Loading/>
                    :
                    roles.length < 1 ?
                        <NoItems/>
                        :
                        <ContentWrapper
                            checked={selectedRolesLength === (roles.length === 0 ? -1 : roles.length)}
                            onSelectAllClick={onSelectAllClick}
                        >
                            <div css={tw`overflow-x-auto`}>
                                <table css={tw`w-full table-auto`}>
                                    <TableHead>
                                        <TableHeader name={'ID'}/>
                                        <TableHeader name={'Name'}/>
                                        <TableHeader name={'Description'}/>
                                    </TableHead>

                                    <TableBody>
                                        {
                                            roles.map(role => (
                                                <TableRow key={role.id}>
                                                    <td css={tw`pl-6`}>
                                                        <RowCheckbox id={role.id}/>
                                                    </td>

                                                    <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{role.id}</td>
                                                    <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                        <NavLink to={`${match.url}/${role.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                            {role.name}
                                                        </NavLink>
                                                    </td>
                                                    <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{role.description}</td>
                                                </TableRow>
                                            ))
                                        }
                                    </TableBody>
                                </table>
                            </div>
                        </ContentWrapper>
                }
            </AdminTable>
        </AdminContentBlock>
    );
};
