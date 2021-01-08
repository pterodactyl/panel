import React, { useContext, useEffect, useState } from 'react';
import getServers, { Context as ServersContext } from '@/api/admin/servers/getServers';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, { ContentWrapper, Loading, NoItems, Pagination, TableBody, TableHead, TableHeader } from '@/components/admin/AdminTable';
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

const RowCheckbox = ({ id }: { id: number }) => {
    const isChecked = AdminContext.useStoreState(state => state.servers.selectedServers.indexOf(id) >= 0);
    const appendSelectedServer = AdminContext.useStoreActions(actions => actions.servers.appendSelectedServer);
    const removeSelectedServer = AdminContext.useStoreActions(actions => actions.servers.removeSelectedServer);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedServer(id);
                } else {
                    removeSelectedServer(id);
                }
            }}
        />
    );
};

const UsersContainer = () => {
    const match = useRouteMatch();

    const { page, setPage } = useContext(ServersContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: servers, error, isValidating } = getServers([ 'node', 'user' ]);

    useEffect(() => {
        if (!error) {
            clearFlashes('servers');
            return;
        }

        clearAndAddHttpError({ key: 'servers', error });
    }, [ error ]);

    const length = servers?.items?.length || 0;

    const setSelectedServers = AdminContext.useStoreActions(actions => actions.servers.setSelectedServers);
    const selectedServerLength = AdminContext.useStoreState(state => state.servers.selectedServers.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedServers(e.currentTarget.checked ? (servers?.items?.map(server => server.id) || []) : []);
    };

    useEffect(() => {
        setSelectedServers([]);
    }, [ page ]);

    return (
        <AdminContentBlock title={'Servers'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Servers</h2>
                    <p css={tw`text-base text-neutral-400`}>All servers available on the system.</p>
                </div>

                <NavLink to={`${match.url}/new`} css={tw`ml-auto`}>
                    <Button type={'button'} size={'large'} css={tw`h-10 px-4 py-0`}>
                        New Server
                    </Button>
                </NavLink>
            </div>

            <FlashMessageRender byKey={'servers'} css={tw`mb-4`}/>

            <AdminTable>
                { servers === undefined || (error && isValidating) ?
                    <Loading/>
                    :
                    length < 1 ?
                        <NoItems/>
                        :
                        <ContentWrapper
                            checked={selectedServerLength === (length === 0 ? -1 : length)}
                            onSelectAllClick={onSelectAllClick}
                        >
                            <Pagination data={servers} onPageSelect={setPage}>
                                <div css={tw`overflow-x-auto`}>
                                    <table css={tw`w-full table-auto`}>
                                        <TableHead>
                                            <TableHeader name={'Identifier'}/>
                                            <TableHeader name={'Name'}/>
                                            <TableHeader name={'Owner'}/>
                                            <TableHeader name={'Node'}/>
                                            <TableHeader name={'Status'}/>
                                        </TableHead>

                                        <TableBody>
                                            {
                                                servers.items.map(server => (
                                                    <tr key={server.id} css={tw`h-14 hover:bg-neutral-600`}>
                                                        <td css={tw`pl-6`}>
                                                            <RowCheckbox id={server.id}/>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={server.identifier}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{server.identifier}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-left whitespace-nowrap`}>
                                                            <NavLink to={`${match.url}/${server.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                {server.name}
                                                            </NavLink>
                                                        </td>

                                                        {/* TODO: Have permission check for displaying user information. */}
                                                        <td css={tw`px-6 text-sm text-left whitespace-nowrap`}>
                                                            <NavLink to={`/admin/users/${server.relations.user?.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                <div css={tw`text-sm text-neutral-200`}>
                                                                    {server.relations.user?.firstName} {server.relations.user?.lastName}
                                                                </div>

                                                                <div css={tw`text-sm text-neutral-400`}>
                                                                    {server.relations.user?.email}
                                                                </div>
                                                            </NavLink>
                                                        </td>

                                                        {/* TODO: Have permission check for displaying node information. */}
                                                        <td css={tw`px-6 text-sm text-left whitespace-nowrap`}>
                                                            <NavLink to={`/admin/nodes/${server.relations.node?.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                <div css={tw`text-sm text-neutral-200`}>
                                                                    {server.relations.node?.name}
                                                                </div>

                                                                <div css={tw`text-sm text-neutral-400`}>
                                                                    {server.relations.node?.fqdn}:{server.relations.node?.daemonListen}
                                                                </div>
                                                            </NavLink>
                                                        </td>

                                                        <td css={tw`px-6 whitespace-nowrap`}>
                                                            { server.isInstalling ?
                                                                <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-yellow-200 text-yellow-800`}>
                                                                    Installing
                                                                </span>
                                                                :
                                                                server.isTransferring ?
                                                                    <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-yellow-200 text-yellow-800`}>
                                                                        Transferring
                                                                    </span>
                                                                    : server.isSuspended ?
                                                                        <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-red-200 text-red-800`}>
                                                                            Suspended
                                                                        </span>
                                                                        :
                                                                        <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}>
                                                                            Active
                                                                        </span>
                                                            }
                                                        </td>
                                                    </tr>
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
        <ServersContext.Provider value={{ page, setPage }}>
            <UsersContainer/>
        </ServersContext.Provider>
    );
};
