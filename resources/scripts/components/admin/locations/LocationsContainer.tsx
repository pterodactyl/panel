import React, { useContext, useEffect, useState } from 'react';
import getLocations, { Context as LocationsContext } from '@/api/admin/locations/getLocations';
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

    const { page, setPage } = useContext(LocationsContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: locations, error, isValidating } = getLocations();

    useEffect(() => {
        if (!error) {
            clearFlashes('locations');
            return;
        }

        clearAndAddHttpError({ error, key: 'locations' });
    }, [ error ]);

    const length = locations?.items?.length || 0;

    const setSelectedLocations = AdminContext.useStoreActions(actions => actions.locations.setSelectedLocations);
    const selectedLocationsLength = AdminContext.useStoreState(state => state.locations.selectedLocations.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedLocations(e.currentTarget.checked ? (locations?.items?.map(location => location.id) || []) : []);
    };

    useEffect(() => {
        setSelectedLocations([]);
    }, [ page ]);

    return (
        <AdminContentBlock title={'Locations'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Locations</h2>
                    <p css={tw`text-base text-neutral-400`}>All locations that nodes can be assigned to for easier categorization.</p>
                </div>

                <NewLocationButton/>
            </div>

            <FlashMessageRender byKey={'locations'} css={tw`mb-4`}/>

            <AdminTable>
                { locations === undefined || (error && isValidating) ?
                    <Loading/>
                    :
                    length < 1 ?
                        <NoItems/>
                        :
                        <ContentWrapper
                            checked={selectedLocationsLength === (length === 0 ? -1 : length)}
                            onSelectAllClick={onSelectAllClick}
                        >
                            <Pagination data={locations} onPageSelect={setPage}>
                                <div css={tw`overflow-x-auto`}>
                                    <table css={tw`w-full table-auto`}>
                                        <TableHead>
                                            <TableHeader name={'ID'}/>
                                            <TableHeader name={'Short Name'}/>
                                            <TableHeader name={'Long Name'}/>
                                        </TableHead>

                                        <TableBody>
                                            {
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
        <LocationsContext.Provider value={{ page, setPage }}>
            <LocationsContainer/>
        </LocationsContext.Provider>
    );
};
