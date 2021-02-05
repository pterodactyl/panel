import React, { useState } from 'react';
import SearchableSelect from '@/components/elements/SearchableSelect';
import searchDatabases from '@/api/admin/databases/searchDatabases';
import { Database } from '@/api/admin/databases/getDatabases';
import tw from 'twin.macro';

export default () => {
    const [ database, setDatabase ] = useState<Database | null>(null);
    const [ databases, setDatabases ] = useState<Database[]>([]);

    const onSearch = (query: string): Promise<void> => {
        return new Promise((resolve, reject) => {
            searchDatabases({ name: query }).then((databases) => {
                setDatabases(databases);
                return resolve();
            }).catch(reject);
        });
    };

    const onSelect = (database: Database) => {
        setDatabase(database);
    };

    return (
        <SearchableSelect
            id="database"
            name="Database"
            items={databases}
            setItems={setDatabases}
            onSearch={onSearch}
            onSelect={onSelect}
            nullable
        >
            {databases.map(d => (
                d.id === database?.id ?
                    <li key={d.id} id={'listbox-item-' + d.id} role="option" css={tw`text-neutral-200 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-neutral-700`} onClick={(e) => {
                        e.stopPropagation();
                        // selectItem(d);
                    }}
                    >
                        <div css={tw`flex items-center`}>
                            <span css={tw`block font-medium truncate`}>
                                {d.name}
                            </span>
                        </div>

                        <span css={tw`absolute inset-y-0 right-0 flex items-center pr-4`}>
                            <svg css={tw`h-5 w-5 text-primary-400`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path clipRule="evenodd" fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                            </svg>
                        </span>
                    </li>
                    :
                    <li key={d.id} id={'listbox-item-' + d.id} role="option" css={tw`text-neutral-200 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-neutral-700`} onClick={(e) => {
                        e.stopPropagation();
                        // selectItem(d);
                    }}
                    >
                        <div css={tw`flex items-center`}>
                            <span css={tw`block font-normal truncate`}>
                                {d.name}
                            </span>
                        </div>
                    </li>
            ))}
        </SearchableSelect>
    );
};
