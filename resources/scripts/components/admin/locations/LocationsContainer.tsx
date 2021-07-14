import React, { useContext, useEffect, useState } from 'react';
import getLocations, { Context as LocationsContext, Filters } from '@/api/admin/locations/getLocations';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, { TableBody, TableHead, TableHeader, TableRow, Pagination, Loading, NoItems, ContentWrapper } from '@/components/admin/AdminTable';
import NewLocationButton from '@/components/admin/locations/NewLocationButton';
import CopyOnClick from '@/components/elements/CopyOnClick';

const RowCheckbox = ({ id }: { id: number}) => {
    const isChecked = AdminContext.useStoreState(state => state.locations.selectedLocations.indexOf(id) >= 0);
    const appendSelectedLocation = AdminContext.useStoreActions(actions => actions.locations.appendSelectedLocation);
    const removeSelectedLocation = AdminContext.useStoreActions(actions => actions.locations.removeSelectedLocation);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedLocation(id);
                } else {
                    removeSelectedLocation(id);
                }
            }}
        />
    );
};

const LocationsContainer = () => {
    const match = useRouteMatch();

    const { page, setPage, setFilters, sort, setSort, sortDirection } = useContext(LocationsContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: locations, error, isValidating } = getLocations();

    useEffect(() => {
        if (!error) {
            clearFlashes('locations');
            return;
        }

        clearAndAddHttpError({ key: 'locations', error });
    }, [ error ]);

    const length = locations?.items?.length || 0;

    const setSelectedLocations = AdminContext.useStoreActions(actions => actions.locations.setSelectedLocations);
    const selectedLocationsLength = AdminContext.useStoreState(state => state.locations.selectedLocations.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedLocations(e.currentTarget.checked ? (locations?.items?.map(location => location.id) || []) : []);
    };

    const onSearch = (query: string): Promise<void> => {
        return new Promise((resolve) => {
            if (query.length < 2) {
                setFilters(null);
            } else {
                setFilters({ short: query });
            }
            return resolve();
        });
    };

    useEffect(() => {
        setSelectedLocations([]);
    }, [ page ]);

    return (
        <AdminContentBlock title={'Locations'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Locations</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>All locations that nodes can be assigned to for easier categorization.</p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NewLocationButton/>
                </div>
            </div>

            <FlashMessageRender byKey={'locations'} css={tw`mb-4`}/>

            <AdminTable>
                <ContentWrapper
                    checked={selectedLocationsLength === (length === 0 ? -1 : length)}
                    onSelectAllClick={onSelectAllClick}
                    onSearch={onSearch}
                >
                    <Pagination data={locations} onPageSelect={setPage}>
                        <div css={tw`overflow-x-auto`}>
                            <table css={tw`w-full table-auto`}>
                                <TableHead>
                                    <TableHeader name={'ID'} direction={sort === 'id' ? (sortDirection ? 1 : 2) : null} onClick={() => setSort('id')}/>
                                    <TableHeader name={'Short Name'} direction={sort === 'short' ? (sortDirection ? 1 : 2) : null} onClick={() => setSort('short')}/>
                                    <TableHeader name={'Long Name'} direction={sort === 'long' ? (sortDirection ? 1 : 2) : null} onClick={() => setSort('long')}/>
                                </TableHead>

                                <TableBody>
                                    { locations !== undefined && !error && !isValidating && length > 0 &&
                                        locations.items.map(location => (
                                            <TableRow key={location.id}>
                                                <td css={tw`pl-6`}>
                                                    <RowCheckbox id={location.id}/>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={location.id.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{location.id}</code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink to={`${match.url}/${location.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                        {location.short}
                                                    </NavLink>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{location.long}</td>
                                            </TableRow>
                                        ))
                                    }
                                </TableBody>
                            </table>

                            { locations === undefined || (error && isValidating) ?
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
        <LocationsContext.Provider value={{ page, setPage, filters, setFilters, sort, setSort, sortDirection, setSortDirection }}>
            <LocationsContainer/>
        </LocationsContext.Provider>
    );
};
