import type { ChangeEvent } from 'react';
import { useContext, useEffect } from 'react';
import type { Filters } from '@/api/admin/servers/getServers';
import getNodes, { Context as NodesContext } from '@/api/admin/nodes/getNodes';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink } from 'react-router-dom';
import tw from 'twin.macro';
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
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { bytesToString, mbToBytes } from '@/lib/formatters';

const RowCheckbox = ({ id }: { id: number }) => {
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
    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(NodesContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: nodes, error, isValidating } = getNodes(['location']);

    useEffect(() => {
        if (!error) {
            clearFlashes('nodes');
            return;
        }

        clearAndAddHttpError({ key: 'nodes', error });
    }, [error]);

    const length = nodes?.items?.length || 0;

    const setSelectedNodes = AdminContext.useStoreActions(actions => actions.nodes.setSelectedNodes);
    const selectedNodesLength = AdminContext.useStoreState(state => state.nodes.selectedNodes.length);

    const onSelectAllClick = (e: ChangeEvent<HTMLInputElement>) => {
        setSelectedNodes(e.currentTarget.checked ? nodes?.items?.map(node => node.id) || [] : []);
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
        setSelectedNodes([]);
    }, [page]);

    return (
        <AdminContentBlock title={'Nodes'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Nodes</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        All nodes available on the system.
                    </p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NavLink to={`/admin/nodes/new`}>
                        <Button type={'button'} size={'large'} css={tw`h-10 px-4 py-0 whitespace-nowrap`}>
                            New Node
                        </Button>
                    </NavLink>
                </div>
            </div>

            <FlashMessageRender byKey={'nodes'} css={tw`mb-4`} />

            <AdminTable>
                <ContentWrapper
                    checked={selectedNodesLength === (length === 0 ? -1 : length)}
                    onSelectAllClick={onSelectAllClick}
                    onSearch={onSearch}
                >
                    <Pagination data={nodes} onPageSelect={setPage}>
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
                                        name={'Location'}
                                        direction={sort === 'location_id' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('location_id')}
                                    />
                                    <TableHeader
                                        name={'FQDN'}
                                        direction={sort === 'fqdn' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('fqdn')}
                                    />
                                    <TableHeader
                                        name={'Total Memory'}
                                        direction={sort === 'memory' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('memory')}
                                    />
                                    <TableHeader
                                        name={'Total Disk'}
                                        direction={sort === 'disk' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => setSort('disk')}
                                    />
                                    <TableHeader />
                                    <TableHeader />
                                </TableHead>

                                <TableBody>
                                    {nodes !== undefined &&
                                        !error &&
                                        !isValidating &&
                                        length > 0 &&
                                        nodes.items.map(node => (
                                            <TableRow key={node.id}>
                                                <td css={tw`pl-6`}>
                                                    <RowCheckbox id={node.id} />
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={node.id.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {node.id}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/nodes/${node.id}`}
                                                        css={tw`text-primary-400 hover:text-primary-300`}
                                                    >
                                                        {node.name}
                                                    </NavLink>
                                                </td>

                                                {/* TODO: Have permission check for displaying location information. */}
                                                <td css={tw`px-6 text-sm text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/locations/${node.relations.location?.id}`}
                                                        css={tw`text-primary-400 hover:text-primary-300`}
                                                    >
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
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                            {node.fqdn}
                                                        </code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    {bytesToString(mbToBytes(node.memory))}
                                                </td>
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    {bytesToString(mbToBytes(node.disk))}
                                                </td>

                                                <td css={tw`px-6 whitespace-nowrap`}>
                                                    {node.scheme === 'https' ? (
                                                        <span
                                                            css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}
                                                        >
                                                            Secure
                                                        </span>
                                                    ) : (
                                                        <span
                                                            css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-red-200 text-red-800`}
                                                        >
                                                            Non-Secure
                                                        </span>
                                                    )}
                                                </td>

                                                <td css={tw`px-6 whitespace-nowrap`}>
                                                    {/* TODO: Change color based off of online/offline status */}
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                        css={[
                                                            tw`h-5 w-5`,
                                                            node.scheme === 'https'
                                                                ? tw`text-green-200`
                                                                : tw`text-red-300`,
                                                        ]}
                                                    >
                                                        <path
                                                            clipRule="evenodd"
                                                            fillRule="evenodd"
                                                            d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                                        />
                                                    </svg>
                                                </td>
                                            </TableRow>
                                        ))}
                                </TableBody>
                            </table>

                            {nodes === undefined || (error && isValidating) ? (
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
        <NodesContext.Provider value={hooks}>
            <NodesContainer />
        </NodesContext.Provider>
    );
};
