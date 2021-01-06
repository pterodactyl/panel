import React, { useContext, useEffect, useState } from 'react';
import getNodes, { Context as NodesContext } from '@/api/admin/nodes/getNodes';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, { TableBody, TableHead, TableHeader, TableRow, Pagination, Loading, NoItems, ContentWrapper } from '@/components/admin/AdminTable';
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { bytesToHuman, megabytesToBytes } from '@/helpers';

const RowCheckbox = ({ id }: { id: number}) => {
    const isChecked = AdminContext.useStoreState(state => state.nodes.selectedNodes.indexOf(id) >= 0);
    const appendSelectedNode = AdminContext.useStoreActions(actions => actions.nodes.appendSelectedNode);
    const removeSelectedNode = AdminContext.useStoreActions(actions => actions.nodes.removeSelectedNode);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedNode(id);
                } else {
                    removeSelectedNode(id);
                }
            }}
        />
    );
};

const NodesContainer = () => {
    const match = useRouteMatch();

    const { page, setPage } = useContext(NodesContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: nodes, error, isValidating } = getNodes([ 'location' ]);

    useEffect(() => {
        if (!error) {
            clearFlashes('nodes');
            return;
        }

        clearAndAddHttpError({ error, key: 'nodes' });
    }, [ error ]);

    const length = nodes?.items?.length || 0;

    const setSelectedNodes = AdminContext.useStoreActions(actions => actions.nodes.setSelectedNodes);
    const selectedNodesLength = AdminContext.useStoreState(state => state.nodes.selectedNodes.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedNodes(e.currentTarget.checked ? (nodes?.items?.map(node => node.id) || []) : []);
    };

    useEffect(() => {
        setSelectedNodes([]);
    }, [ page ]);

    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Nodes</h2>
                    <p css={tw`text-base text-neutral-400`}>All nodes available on the system.</p>
                </div>

                <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`}>
                    New Node
                </Button>
            </div>

            <FlashMessageRender byKey={'nodes'} css={tw`mb-4`}/>

            <AdminTable>
                { nodes === undefined || (error && isValidating) ?
                    <Loading/>
                    :
                    length < 1 ?
                        <NoItems/>
                        :
                        <ContentWrapper
                            checked={selectedNodesLength === (length === 0 ? -1 : length)}
                            onSelectAllClick={onSelectAllClick}
                        >
                            <Pagination data={nodes} onPageSelect={setPage}>
                                <div css={tw`overflow-x-auto`}>
                                    <table css={tw`w-full table-auto`}>
                                        <TableHead>
                                            <TableHeader name={'ID'}/>
                                            <TableHeader name={'Name'}/>
                                            <TableHeader name={'Location'}/>
                                            <TableHeader name={'FQDN'}/>
                                            <TableHeader name={'Total Memory'}/>
                                            <TableHeader name={'Total Disk'}/>
                                            <th css={tw`px-6 py-2`}/>
                                        </TableHead>

                                        <TableBody>
                                            {
                                                nodes.items.map(node => (
                                                    <TableRow key={node.id}>
                                                        <td css={tw`pl-6`}>
                                                            <RowCheckbox id={node.id}/>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={node.id.toString()}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{node.id}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <NavLink to={`${match.url}/${node.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                {node.name}
                                                            </NavLink>
                                                        </td>

                                                        {/* TODO: Have permission check for displaying location information. */}
                                                        <td css={tw`px-6 text-sm text-left whitespace-nowrap`}>
                                                            <NavLink to={`/admin/locations/${node.relations.location?.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                <div css={tw`text-sm text-neutral-200`}>
                                                                    {node.relations.location?.short}
                                                                </div>

                                                                <div css={tw`text-sm text-neutral-400`}>
                                                                    {node.relations.location?.long}
                                                                </div>
                                                            </NavLink>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={node.fqdn}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{node.fqdn}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{bytesToHuman(megabytesToBytes(node.memory))}</td>
                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{bytesToHuman(megabytesToBytes(node.disk))}</td>

                                                        <td css={tw`px-6 whitespace-nowrap`}>
                                                            { node.scheme === 'https' ?
                                                                <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}>
                                                                    Secure
                                                                </span>
                                                                :
                                                                <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-red-200 text-red-800`}>
                                                                    Non-Secure
                                                                </span>
                                                            }
                                                        </td>
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
        <NodesContext.Provider value={{ page, setPage }}>
            <NodesContainer/>
        </NodesContext.Provider>
    );
};
