import { useFormikContext } from 'formik';
import { useState } from 'react';

import type { Location } from '@/api/admin/locations/getLocations';
import searchLocations from '@/api/admin/locations/searchLocations';
import SearchableSelect, { Option } from '@/components/elements/SearchableSelect';

export default ({ selected }: { selected: Location | null }) => {
    const context = useFormikContext();

    const [location, setLocation] = useState<Location | null>(selected);
    const [locations, setLocations] = useState<Location[] | null>(null);

    const onSearch = (query: string): Promise<void> => {
        return new Promise((resolve, reject) => {
            searchLocations({ short: query })
                .then(locations => {
                    setLocations(locations);
                    return resolve();
                })
                .catch(reject);
        });
    };

    const onSelect = (location: Location | null) => {
        setLocation(location);
        context.setFieldValue('locationId', location?.id || null);
    };

    const getSelectedText = (location: Location | null): string | undefined => {
        return location?.short;
    };

    return (
        <SearchableSelect
            id={'locationId'}
            name={'locationId'}
            label={'Location'}
            placeholder={'Select a location...'}
            items={locations}
            selected={location}
            setSelected={setLocation}
            setItems={setLocations}
            onSearch={onSearch}
            onSelect={onSelect}
            getSelectedText={getSelectedText}
            nullable
        >
            {locations?.map(d => (
                <Option key={d.id} selectId={'locationId'} id={d.id} item={d} active={d.id === location?.id}>
                    {d.short}
                </Option>
            ))}
        </SearchableSelect>
    );
};
