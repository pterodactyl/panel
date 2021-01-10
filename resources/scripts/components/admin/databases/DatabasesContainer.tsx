import CopyOnClick from '@/components/elements/CopyOnClick';
import React, { useContext, useEffect, useState } from 'react';
import getDatabases, { Context as DatabasesContext } from '@/api/admin/databases/getDatabases';
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
    const isChecked = AdminContext.useStoreState(state => state.databases.selectedDatabases.indexOf(id) >= 0);
    const appendSelectedDatabase = AdminContext.useStoreActions(actions => actions.databases.appendSelectedDatabase);
    const removeSelectedDatabase = AdminContext.useStoreActions(actions => actions.databases.removeSelectedDatabase);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedDatabase(id);
                } else {
                    removeSelectedDatabase(id);
                }
            }}
        />
    );
};

const DatabasesContainer = () => {
    const match = useRouteMatch();

    const { page, setPage } = useContext(DatabasesContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: databases, error, isValidating } = getDatabases();

    useEffect(() => {
        if (!error) {
            clearFlashes('databases');
            return;
        }

        clearAndAddHttpError({ key: 'databases', error });
    }, [ error ]);

    const length = databases?.items?.length || 0;

    const setSelectedDatabases = AdminContext.useStoreActions(actions => actions.databases.setSelectedDatabases);
    const selectedDatabasesLength = AdminContext.useStoreState(state => state.databases.selectedDatabases.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedDatabases(e.currentTarget.checked ? (databases?.items?.map(database => database.id) || []) : []);
    };

    useEffect(() => {
        setSelectedDatabases([]);
    }, [ page ]);

    return (
        <AdminContentBlock title={'Databases'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Database Hosts</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>Database hosts that servers can have databases created on.</p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NavLink to={`${match.url}/new`}>
                        <Button type={'button'} size={'large'} css={tw`h-10 px-4 py-0 whitespace-nowrap`}>
                            New Database Host
                        </Button>
                    </NavLink>
                </div>
            </div>

            <FlashMessageRender byKey={'databases'} css={tw`mb-4`}/>

            <AdminTable>
                { databases === undefined || (error && isValidating) ?
                    <Loading/>
                    :
                    length < 1 ?
                        <NoItems/>
                        :
                        <ContentWrapper
                            checked={selectedDatabasesLength === (length === 0 ? -1 : length)}
                            onSelectAllClick={onSelectAllClick}
                        >
                            <Pagination data={databases} onPageSelect={setPage}>
                                <div css={tw`overflow-x-auto`}>
                                    <table css={tw`w-full table-auto`}>
                                        <TableHead>
                                            <TableHeader name={'ID'}/>
                                            <TableHeader name={'Name'}/>
                                            <TableHeader name={'Address'}/>
                                            <TableHeader name={'Username'}/>
                                        </TableHead>

                                        <TableBody>
                                            {
                                                databases.items.map(database => (
                                                    <TableRow key={database.id}>
                                                        <td css={tw`pl-6`}>
                                                            <RowCheckbox id={database.id}/>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={database.id.toString()}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{database.id}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <NavLink to={`${match.url}/${database.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                {database.name}
                                                            </NavLink>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={database.getAddress()}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{database.getAddress()}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{database.username}</td>
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
        <DatabasesContext.Provider value={{ page, setPage }}>
            <DatabasesContainer/>
        </DatabasesContext.Provider>
    );
};
