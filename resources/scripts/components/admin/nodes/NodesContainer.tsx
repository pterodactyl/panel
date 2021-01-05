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
    const { data: nodes, error, isValidating } = getNodes();

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
                                        </TableHead>

                                        <TableBody>
                                            {
                                                nodes.items.map(node => (
                                                    <TableRow key={node.id}>
                                                        <td css={tw`pl-6`}>
                                                            <RowCheckbox id={node.id}/>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{node.id}</td>
                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <NavLink to={`${match.url}/${node.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                {node.name}
                                                            </NavLink>
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
