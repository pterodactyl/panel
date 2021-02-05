import React, { useEffect, useState } from 'react';
import styled from 'styled-components/macro';
import tw from 'twin.macro';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import { Location } from '@/api/admin/locations/getLocations';
import searchLocations from '@/api/admin/locations/searchLocations';
import InputSpinner from '@/components/elements/InputSpinner';
import { debounce } from 'debounce';

const Dropdown = styled.div<{ expanded: boolean }>`
    ${tw`absolute mt-1 w-full rounded-md bg-neutral-900 shadow-lg z-10`};
    ${props => !props.expanded && tw`hidden`};
`;

export default ({ defaultLocation }: { defaultLocation: Location }) => {
    const [ loading, setLoading ] = useState(false);
    const [ expanded, setExpanded ] = useState(false);
    const [ location, setLocation ] = useState<Location>(defaultLocation);
    const [ locations, setLocations ] = useState<Location[]>([]);

    const [ inputText, setInputText ] = useState('');

    const onFocus = () => {
        setInputText('');
        setLocations([]);
        setExpanded(true);
    };

    const search = debounce((query: string) => {
        if (!expanded) {
            return;
        }

        if (query === '' || query.length < 2) {
            setLocations([]);
            return;
        }

        setLoading(true);
        searchLocations({ short: query }).then((locations) => {
            setLocations(locations);
        }).then(() => setLoading(false));
    }, 250);

    const selectLocation = (location: Location) => {
        setLocation(location);
    };

    useEffect(() => {
        setInputText(location.short);
        setExpanded(false);
    }, [ location ]);

    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (e.key !== 'Escape') {
                return;
            }

            setInputText(location.short);
            setExpanded(false);
        };

        window.addEventListener('keydown', handler);
        return () => {
            window.removeEventListener('keydown', handler);
        };
    }, [ expanded ]);

    return (
        <div>
            <Label htmlFor="listbox-label">Location</Label>

            <div css={tw`mt-1 relative`}>
                <InputSpinner visible={loading}>
                    <Input type="text" className="ignoreReadOnly" value={inputText} readOnly={!expanded} onFocus={onFocus} onChange={e => {
                        setInputText(e.currentTarget.value);
                        search(e.currentTarget.value);
                    }}
                    />
                </InputSpinner>

                <div css={tw`ml-3 absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none`}>
                    <svg css={tw`h-5 w-5 text-neutral-400`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path clipRule="evenodd" fillRule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"/>
                    </svg>
                </div>

                <Dropdown expanded={expanded}>
                    {locations.length < 1 ?
                        inputText.length < 2 ?
                            <div css={tw`h-10 flex flex-row items-center px-3`}>
                                <p css={tw`text-sm`}>Please type 2 or more characters.</p>
                            </div>
                            :
                            <div css={tw`h-10 flex flex-row items-center px-3`}>
                                <p css={tw`text-sm`}>No results found.</p>
                            </div>
                        :
                        <ul tabIndex={-1} role="listbox" aria-labelledby="listbox-label" aria-activedescendant="listbox-item-3" css={tw`max-h-56 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm`}>
                            {locations.map(l => (
                                l.id === location.id ?
                                    <li key={l.id} id={'listbox-item-' + l.id} role="option" css={tw`text-neutral-200 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-neutral-700`} onClick={(e) => {
                                        e.stopPropagation();
                                        selectLocation(l);
                                    }}
                                    >
                                        <div css={tw`flex items-center`}>
                                            <span css={tw`block font-medium truncate`}>
                                                {l.short}
                                            </span>
                                        </div>

                                        <span css={tw`absolute inset-y-0 right-0 flex items-center pr-4`}>
                                            <svg css={tw`h-5 w-5 text-primary-400`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path clipRule="evenodd" fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                            </svg>
                                        </span>
                                    </li>
                                    :
                                    <li key={l.id} id={'listbox-item-' + l.id} role="option" css={tw`text-neutral-200 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-neutral-700`} onClick={(e) => {
                                        e.stopPropagation();
                                        selectLocation(l);
                                    }}
                                    >
                                        <div css={tw`flex items-center`}>
                                            <span css={tw`block font-normal truncate`}>
                                                {l.short}
                                            </span>
                                        </div>
                                    </li>
                            ))}
                        </ul>
                    }
                </Dropdown>
            </div>
        </div>
    );
};
