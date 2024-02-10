import { debounce } from 'debounce';
import React, { createRef, ReactElement, useEffect, useState } from 'react';
import tw, { styled } from 'twin.macro';
import Input from '@/components/elements/Input';
import InputSpinner from '@/components/elements/InputSpinner';
import Label from '@/components/elements/Label';

const Dropdown = styled.div<{ expanded: boolean }>`
    ${tw`absolute z-10 w-full mt-1 rounded-md shadow-lg bg-neutral-900`};
    ${props => !props.expanded && tw`hidden`};
`;

interface OptionProps<T> {
    selectId: string;
    id: number;
    item: T;
    active: boolean;

    isHighlighted?: boolean;
    onClick?: (item: T) => (e: React.MouseEvent) => void;

    children: React.ReactNode;
}

interface IdObj {
    id: number;
}

export const Option = <T extends IdObj>({
    selectId,
    id,
    item,
    active,
    isHighlighted,
    onClick,
    children,
}: OptionProps<T>) => {
    if (isHighlighted === undefined) {
        isHighlighted = false;
    }

    // This should never be true, but just in-case we set it to an empty function to make sure shit doesn't blow up.
    if (onClick === undefined) {
        // eslint-disable-next-line @typescript-eslint/no-empty-function
        onClick = () => () => {};
    }

    if (active) {
        return (
            <li
                id={selectId + '-select-item-' + id}
                role="option"
                css={[
                    tw`relative py-2 pl-3 cursor-pointer select-none text-neutral-200 pr-9 hover:bg-neutral-700`,
                    isHighlighted ? tw`bg-neutral-700` : null,
                ]}
                onClick={onClick(item)}
            >
                <div css={tw`flex items-center`}>
                    <span css={tw`block font-medium truncate`}>{children}</span>
                </div>

                <span css={tw`absolute inset-y-0 right-0 flex items-center pr-4`}>
                    <svg
                        css={tw`w-5 h-5 text-primary-400`}
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            clipRule="evenodd"
                            fillRule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        />
                    </svg>
                </span>
            </li>
        );
    }

    return (
        <li
            id={selectId + 'select-item-' + id}
            role="option"
            css={[
                tw`relative py-2 pl-3 cursor-pointer select-none text-neutral-200 pr-9 hover:bg-neutral-700`,
                isHighlighted ? tw`bg-neutral-700` : null,
            ]}
            onClick={onClick(item)}
        >
            <div css={tw`flex items-center`}>
                <span css={tw`block font-normal truncate`}>{children}</span>
            </div>
        </li>
    );
};

interface SearchableSelectProps<T> {
    id: string;
    name: string;
    label: string;
    placeholder?: string;
    nullable?: boolean;

    selected: T | null;
    setSelected: (item: T | null) => void;

    items: T[] | null;
    setItems: (items: T[] | null) => void;

    onSearch: (query: string) => Promise<void>;
    onSelect: (item: T | null) => void;

    getSelectedText: (item: T | null) => string | undefined;

    children: React.ReactNode;
    className?: string;
}

export const SearchableSelect = <T extends IdObj>({
    id,
    name,
    label,
    placeholder,
    selected,
    setSelected,
    items,
    setItems,
    onSearch,
    onSelect,
    getSelectedText,
    children,
    className,
}: SearchableSelectProps<T>) => {
    const [loading, setLoading] = useState(false);
    const [expanded, setExpanded] = useState(false);

    const [inputText, setInputText] = useState('');

    const [highlighted, setHighlighted] = useState<number | null>(null);

    const searchInput = createRef<HTMLInputElement>();
    const itemsList = createRef<HTMLDivElement>();

    const onFocus = () => {
        setInputText('');
        setItems(null);
        setExpanded(true);
        setHighlighted(null);
    };

    const onBlur = () => {
        setInputText(getSelectedText(selected) || '');
        setItems(null);
        setExpanded(false);
        setHighlighted(null);
    };

    const search = debounce((query: string) => {
        if (!expanded) {
            return;
        }

        if (query === '' || query.length < 2) {
            setItems(null);
            setHighlighted(null);
            return;
        }

        setLoading(true);
        onSearch(query).then(() => setLoading(false));
    }, 250);

    const handleInputKeydown = (e: React.KeyboardEvent) => {
        if (e.key === 'Tab' || e.key === 'Escape') {
            onBlur();
            return;
        }

        if (!items) {
            return;
        }

        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            // Prevent up and down arrows from moving the cursor in the input.
            e.preventDefault();

            if (highlighted === null) {
                setHighlighted(items[0].id);
                return;
            }

            const item = items.find(i => i.id === highlighted);
            if (!item) {
                return;
            }

            let index = items.indexOf(item);
            if (e.key === 'ArrowUp') {
                if (--index < 0) {
                    return;
                }
            } else {
                if (++index >= items.length) {
                    return;
                }
            }

            setHighlighted(items[index].id);
            return;
        }

        // Prevent the form from being submitted if the user accidentally hits enter
        // while focused on the select.
        if (e.key === 'Enter') {
            e.preventDefault();

            const item = items.find(i => i.id === highlighted);
            if (!item) {
                return;
            }

            setSelected(item);
            onSelect(item);
        }
    };

    useEffect(() => {
        const clickHandler = (e: MouseEvent) => {
            const input = searchInput.current;
            const menu = itemsList.current;

            if (e.button === 2 || !expanded || !input || !menu) {
                return;
            }

            if (e.target === input || input.contains(e.target as Node)) {
                return;
            }

            if (e.target === menu || menu.contains(e.target as Node)) {
                return;
            }

            if (e.target === input || input.contains(e.target as Node)) {
                return;
            }

            if (e.target === menu || menu.contains(e.target as Node)) {
                return;
            }

            onBlur();
        };

        const contextmenuHandler = () => {
            onBlur();
        };

        window.addEventListener('mousedown', clickHandler);
        window.addEventListener('contextmenu', contextmenuHandler);
        return () => {
            window.removeEventListener('mousedown', clickHandler);
            window.removeEventListener('contextmenu', contextmenuHandler);
        };
    }, [expanded]);

    const onClick = (item: T) => () => {
        onSelect(item);

        setExpanded(false);
        setInputText(getSelectedText(selected) || '');
    };

    useEffect(() => {
        if (expanded) {
            return;
        }

        setInputText(getSelectedText(selected) || '');
    }, [selected]);

    // This shit is really stupid but works, so is it really stupid?
    const c = React.Children.map(children, child =>
        React.cloneElement(child as ReactElement, {
            isHighlighted: ((child as ReactElement).props as OptionProps<T>).id === highlighted,
            onClick: onClick.bind(child),
        }),
    );

    return (
        <div className={className}>
            <div css={tw`flex flex-row`}>
                <Label htmlFor={id + '-select-label'}>{label}</Label>
            </div>

            <div css={tw`relative`}>
                <InputSpinner visible={loading}>
                    <Input
                        ref={searchInput}
                        type={'search'}
                        id={id}
                        name={name}
                        value={inputText}
                        readOnly={!expanded}
                        onFocus={onFocus}
                        onChange={e => {
                            setInputText(e.currentTarget.value);
                            search(e.currentTarget.value);
                        }}
                        onKeyDown={handleInputKeydown}
                        className={'ignoreReadOnly'}
                        placeholder={placeholder}
                    />
                </InputSpinner>

                <div
                    css={[
                        tw`absolute inset-y-0 right-0 flex items-center pr-2 ml-3`,
                        !expanded && tw`pointer-events-none`,
                    ]}
                >
                    {inputText !== '' && expanded && (
                        <svg
                            css={tw`w-5 h-5 text-neutral-400 cursor-pointer`}
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            onMouseDown={e => {
                                e.preventDefault();
                                setInputText('');
                            }}
                        >
                            <path
                                clipRule="evenodd"
                                fillRule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            />
                        </svg>
                    )}
                    <svg
                        css={tw`w-5 h-5 text-neutral-400 pointer-events-none`}
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            clipRule="evenodd"
                            fillRule="evenodd"
                            d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        />
                    </svg>
                </div>

                <Dropdown ref={itemsList} expanded={expanded}>
                    {items === null || items.length < 1 ? (
                        items === null || inputText.length < 2 ? (
                            <div css={tw`flex flex-row items-center h-10 px-3`}>
                                <p css={tw`text-sm`}>Please type 2 or more characters.</p>
                            </div>
                        ) : (
                            <div css={tw`flex flex-row items-center h-10 px-3`}>
                                <p css={tw`text-sm`}>No results found.</p>
                            </div>
                        )
                    ) : (
                        <ul
                            tabIndex={-1}
                            role={id + '-select'}
                            aria-labelledby={id + '-select-label'}
                            aria-activedescendant={id + '-select-item-' + selected?.id}
                            css={tw`py-2 overflow-auto text-base rounded-md max-h-56 ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm`}
                        >
                            {c}
                        </ul>
                    )}
                </Dropdown>
            </div>
        </div>
    );
};

export default SearchableSelect;
