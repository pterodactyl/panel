import React from 'react';
import { Combobox } from '@base-ui/react/combobox';
import { Check, ChevronDown } from 'lucide-react';
import { isNumber, isString } from '@/lib/objects';
import { useSkin, type SkinBinding } from '@/themes/skins';

export type SelectValue = string | number;

export type SelectOption = {
    value: SelectValue;
    label: React.ReactNode;
    textLabel?: string;
    disabled?: boolean;
};

export interface SelectGroup {
    id?: SelectValue;
    label: string;
    options: SelectOption[];
}

interface BaseSelectProps {
    id?: string;
    options?: SelectOption[];
    groups?: SelectGroup[];
    disabled?: boolean;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    hasError?: boolean;
    /** Keeps the supplied options available while using the built-in search filter. */
    filterOptions?: boolean;
    'aria-describedby'?: string;
    'aria-invalid'?: boolean;
    onBlur?: React.FocusEventHandler<HTMLInputElement>;
    /** Hands the typed query to the caller. */
    onSearchChange?: (value: string) => void;
}

interface SingleSelectProps extends BaseSelectProps {
    multiple?: false;
    value: SelectValue | null | undefined;
    onChange: (value: SelectValue) => void;
}

interface MultiSelectProps extends BaseSelectProps {
    multiple: true;
    value: SelectValue[];
    onChange: (value: SelectValue[]) => void;
    /** Offers typed text that matches no option label, and receives it when picked. */
    onCreate?: (text: string) => void;
}

export type SelectProps = SingleSelectProps | MultiSelectProps;

type ComboboxGroupItem = {
    value: string;
    label: string;
    items: SelectOption[];
};

type SelectSkin = SkinBinding<'Select'>;

const optionLabel = (option: SelectOption | null | undefined): string => {
    if (!option) {
        return '';
    }

    if (option.textLabel !== undefined) {
        return option.textLabel;
    }

    if (isString(option.label) || isNumber(option.label)) {
        return String(option.label);
    }

    return String(option.value);
};

const isTextLabel = (label: React.ReactNode): boolean => isString(label) || isNumber(label);

const createValue = '\u0000create';

const createOption = (text: string, options: SelectOption[]): SelectOption | null => {
    const query = text.trim();

    if (query === '' || options.some((option) => optionLabel(option).toLowerCase() === query.toLowerCase())) {
        return null;
    }

    return { value: createValue, label: `Create "${query}"`, textLabel: query };
};

const sameValue = (left: SelectValue, right: SelectValue): boolean => Object.is(left, right);

const isSameOption = (option: SelectOption, selected: SelectOption): boolean => sameValue(option.value, selected.value);

const optionKey = (option: Pick<SelectOption, 'value'>): string =>
    `${isNumber(option.value) ? 'number' : 'string'}:${String(option.value)}`;

const getOptions = (options: SelectOption[] | undefined, groups: SelectGroup[] | undefined): SelectOption[] =>
    options ?? groups?.flatMap((group) => group.options) ?? [];

const getGroups = (groups: SelectGroup[] | undefined): ComboboxGroupItem[] | undefined =>
    groups?.map((group, index) => ({
        value: group.id === undefined ? `${index}:${group.label}` : optionKey({ value: group.id }),
        label: group.label,
        items: group.options,
    }));

const CheckIcon = () => <Check size={14} strokeWidth={2.5} aria-hidden />;

const ChevronIcon = () => <ChevronDown size={16} aria-hidden />;

const ComboboxOptionItem = ({
    ui,
    option,
    showIndicator,
}: {
    ui: SelectSkin;
    option: SelectOption;
    showIndicator: boolean;
}) => (
    <Combobox.Item {...ui('item')} value={option} disabled={option.disabled}>
        <span {...ui('itemLabel')}>{option.label}</span>
        {showIndicator && (
            <Combobox.ItemIndicator {...ui('indicator')}>
                <CheckIcon />
            </Combobox.ItemIndicator>
        )}
    </Combobox.Item>
);

const scrollOptions = (event: React.WheelEvent<HTMLElement>) => {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.scrollTop += event.deltaY;
};

const preserveScrollbarInteraction = (event: React.PointerEvent<HTMLElement>) => {
    const element = event.currentTarget;
    const scrollbarWidth = element.offsetWidth - element.clientWidth;

    if (scrollbarWidth <= 0) {
        return;
    }

    const scrollbarStart = element.getBoundingClientRect().right - scrollbarWidth;

    if (event.clientX < scrollbarStart) {
        return;
    }

    event.stopPropagation();
};

const ComboboxOptionsList = ({
    ui,
    groups,
    showIndicator,
}: {
    ui: SelectSkin;
    groups: ComboboxGroupItem[] | undefined;
    showIndicator: boolean;
}) =>
    groups ? (
        <Combobox.List {...ui('list')} onWheel={scrollOptions} onPointerDown={preserveScrollbarInteraction}>
            {(group: ComboboxGroupItem) => (
                <Combobox.Group key={group.value} items={group.items} {...ui('group')}>
                    <Combobox.GroupLabel {...ui('groupLabel')}>{group.label}</Combobox.GroupLabel>
                    <Combobox.Collection>
                        {(option: SelectOption) => (
                            <ComboboxOptionItem
                                key={optionKey(option)}
                                ui={ui}
                                option={option}
                                showIndicator={showIndicator}
                            />
                        )}
                    </Combobox.Collection>
                </Combobox.Group>
            )}
        </Combobox.List>
    ) : (
        <Combobox.List {...ui('list')} onWheel={scrollOptions} onPointerDown={preserveScrollbarInteraction}>
            {(option: SelectOption) => (
                <ComboboxOptionItem key={optionKey(option)} ui={ui} option={option} showIndicator={showIndicator} />
            )}
        </Combobox.List>
    );

const ComboboxPopupContent = ({
    ui,
    groupedItems,
    emptyMessage,
    showIndicator,
}: {
    ui: SelectSkin;
    groupedItems: ComboboxGroupItem[] | undefined;
    emptyMessage: string;
    showIndicator: boolean;
}) => (
    <Combobox.Portal>
        <Combobox.Positioner {...ui('positioner')} sideOffset={4}>
            <Combobox.Popup {...ui('popup')}>
                <Combobox.Empty {...ui('empty')}>{emptyMessage}</Combobox.Empty>
                <ComboboxOptionsList ui={ui} groups={groupedItems} showIndicator={showIndicator} />
            </Combobox.Popup>
        </Combobox.Positioner>
    </Combobox.Portal>
);

const singleSelectedValues = (value: SelectValue | null | undefined): SelectValue[] =>
    value === null || value === undefined ? [] : [value];

const findOption = (candidates: SelectOption[], value: SelectValue): SelectOption | undefined =>
    candidates.find((option) => sameValue(option.value, value));

const resolveSelectedOptions = (
    values: SelectValue[],
    flatOptions: SelectOption[],
    fallback: SelectOption[]
): SelectOption[] =>
    values.flatMap((value) => {
        const option = findOption(flatOptions, value) ?? findOption(fallback, value);

        return option ? [option] : [];
    });

const selectionSignature = (selected: SelectOption[]): string =>
    selected.map((option) => `${optionKey(option)}:${optionLabel(option)}`).join('\n');

// Keeps the label of a selected value that is no longer listed, e.g. after a server-side search.
function useSelectedOptions(flatOptions: SelectOption[], values: SelectValue[]): SelectOption[] {
    const [remembered, setRemembered] = React.useState(() => resolveSelectedOptions(values, flatOptions, []));
    const selected = resolveSelectedOptions(values, flatOptions, remembered);

    if (selectionSignature(selected) !== selectionSignature(remembered)) {
        setRemembered(selected);
    }

    return selected;
}

const resolveRichSelection = (selectedOption: SelectOption | null): React.ReactNode | null =>
    selectedOption && !isTextLabel(selectedOption.label) ? selectedOption.label : null;

function useSelectSearch({
    onBlur,
    onSearchChange,
    filterOptions = false,
}: {
    onBlur?: React.FocusEventHandler<HTMLInputElement>;
    onSearchChange?: (value: string) => void;
    filterOptions?: boolean;
}) {
    const [searchValue, setSearchValue] = React.useState('');
    const [inputFocused, setInputFocused] = React.useState(false);

    const resetInputValue = React.useCallback(() => {
        setSearchValue('');
        onSearchChange?.('');
    }, [onSearchChange]);

    const handleInputValueChange = React.useCallback(
        (value: string, eventDetails: { reason?: string }) => {
            if (eventDetails.reason === 'focus-out' || eventDetails.reason === 'input-blur' || !inputFocused) {
                resetInputValue();

                return;
            }

            setSearchValue(value);
            onSearchChange?.(value);
        },
        [inputFocused, onSearchChange, resetInputValue]
    );

    const handleFocus: React.FocusEventHandler<HTMLInputElement> = React.useCallback(() => {
        setInputFocused(true);
    }, []);

    const handleBlur: React.FocusEventHandler<HTMLInputElement> = React.useCallback(
        (event) => {
            setInputFocused(false);
            resetInputValue();
            onBlur?.(event);
        },
        [onBlur, resetInputValue]
    );

    return {
        searchValue,
        setSearchValue,
        inputFocused,
        resetInputValue,
        handleInputValueChange,
        handleFocus,
        handleBlur,
        externalFilter: filterOptions || !onSearchChange ? undefined : null,
    };
}

type SelectSearch = ReturnType<typeof useSelectSearch>;

type InputAriaProps = Pick<React.ComponentProps<'input'>, 'aria-describedby' | 'aria-invalid'>;

function MultiSelectCombobox({
    ui,
    id,
    placeholder,
    searchPlaceholder,
    emptyMessage,
    disabled,
    items,
    groupedItems,
    selectedOptions,
    inputValue,
    inputAria,
    search,
    onChange,
    onCreate,
}: {
    ui: SelectSkin;
    id: string | undefined;
    placeholder: string | undefined;
    searchPlaceholder: string;
    emptyMessage: string;
    disabled: boolean | undefined;
    items: SelectOption[] | ComboboxGroupItem[];
    groupedItems: ComboboxGroupItem[] | undefined;
    selectedOptions: SelectOption[];
    inputValue: string;
    inputAria: InputAriaProps;
    search: SelectSearch;
    onChange: (value: SelectValue[]) => void;
    onCreate: ((text: string) => void) | undefined;
}) {
    return (
        <Combobox.Root<SelectOption, true>
            multiple
            items={items}
            value={selectedOptions}
            inputValue={inputValue}
            onInputValueChange={search.handleInputValueChange}
            onOpenChange={(open) => {
                if (!open) {
                    search.resetInputValue();
                }
            }}
            onValueChange={(value) => {
                const created = value.find((option) => option.value === createValue);

                if (created && onCreate) {
                    onCreate(optionLabel(created));
                } else {
                    onChange(value.map((option) => option.value));
                }

                search.resetInputValue();
            }}
            filter={search.externalFilter}
            disabled={disabled}
            itemToStringLabel={optionLabel}
            itemToStringValue={(option) => String(option?.value ?? '')}
            isItemEqualToValue={isSameOption}
            autoHighlight
        >
            <Combobox.InputGroup {...ui('root')}>
                <Combobox.Value>
                    {(value: SelectOption[]) => (
                        <>
                            {value.length > 0 && <span {...ui('summary')}>{`${value.length} selected`}</span>}
                            <Combobox.Input
                                {...ui('input')}
                                {...inputAria}
                                id={id}
                                placeholder={value.length > 0 ? searchPlaceholder : placeholder}
                                onFocus={search.handleFocus}
                                onBlur={search.handleBlur}
                            />
                        </>
                    )}
                </Combobox.Value>
                <Combobox.Trigger {...ui('trigger')} aria-label='Open popup'>
                    <ChevronIcon />
                </Combobox.Trigger>
            </Combobox.InputGroup>
            <ComboboxPopupContent ui={ui} groupedItems={groupedItems} emptyMessage={emptyMessage} showIndicator />
        </Combobox.Root>
    );
}

function SingleSelectCombobox({
    ui,
    id,
    placeholder,
    emptyMessage,
    disabled,
    items,
    groupedItems,
    selectedOption,
    inputValue,
    richSelection,
    inputAria,
    search,
    onChange,
    onSelectLabel,
}: {
    ui: SelectSkin;
    id: string | undefined;
    placeholder: string | undefined;
    emptyMessage: string;
    disabled: boolean | undefined;
    items: SelectOption[] | ComboboxGroupItem[];
    groupedItems: ComboboxGroupItem[] | undefined;
    selectedOption: SelectOption | null;
    inputValue: string;
    richSelection: React.ReactNode | null;
    inputAria: InputAriaProps;
    search: SelectSearch;
    onChange: (value: SelectValue) => void;
    onSelectLabel: (label: string) => void;
}) {
    return (
        <Combobox.Root<SelectOption>
            items={items}
            value={selectedOption}
            inputValue={inputValue}
            onInputValueChange={search.handleInputValueChange}
            onOpenChange={(open) => {
                if (!open) {
                    search.resetInputValue();
                }
            }}
            onValueChange={(option) => {
                if (option) {
                    onChange(option.value);
                    onSelectLabel(optionLabel(option));
                }
            }}
            filter={search.externalFilter}
            disabled={disabled}
            itemToStringLabel={optionLabel}
            itemToStringValue={(option) => String(option?.value ?? '')}
            isItemEqualToValue={isSameOption}
            autoHighlight
        >
            <Combobox.InputGroup {...ui('root')}>
                {richSelection && <span {...ui('preview')}>{richSelection}</span>}
                <Combobox.Input
                    {...ui('input')}
                    {...inputAria}
                    id={id}
                    placeholder={placeholder}
                    onFocus={search.handleFocus}
                    onBlur={search.handleBlur}
                />
                <Combobox.Trigger {...ui('trigger')} aria-label='Open popup'>
                    <ChevronIcon />
                </Combobox.Trigger>
            </Combobox.InputGroup>
            <ComboboxPopupContent
                ui={ui}
                groupedItems={groupedItems}
                emptyMessage={emptyMessage}
                showIndicator={false}
            />
        </Combobox.Root>
    );
}

export default function Select(props: SelectProps) {
    const {
        id,
        options,
        groups,
        disabled,
        placeholder,
        searchPlaceholder = 'Search...',
        emptyMessage = 'No results found.',
        hasError,
        filterOptions = false,
        onBlur,
        onSearchChange,
    } = props;
    const flatOptions = getOptions(options, groups);
    const groupedItems = getGroups(groups);
    const items = groupedItems ?? flatOptions;
    const search = useSelectSearch({ onBlur, onSearchChange, filterOptions });
    const selectedValues = props.multiple ? props.value : singleSelectedValues(props.value);
    const selectedOptions = useSelectedOptions(flatOptions, selectedValues);
    const inputAria: InputAriaProps = {
        'aria-describedby': props['aria-describedby'],
        'aria-invalid': props['aria-invalid'],
    };
    const richSelection = props.multiple ? null : resolveRichSelection(selectedOptions[0] ?? null);
    const ui = useSkin('Select', {
        error: !!hasError,
        multiple: !!props.multiple,
        selected: selectedOptions.length > 0,
        richLabel: !!richSelection,
        searching: search.inputFocused,
    });

    if (props.multiple) {
        const inputValue = search.inputFocused ? search.searchValue : '';
        const creatable = props.onCreate && !groupedItems ? createOption(inputValue, flatOptions) : null;

        return (
            <MultiSelectCombobox
                ui={ui}
                id={id}
                placeholder={placeholder}
                searchPlaceholder={searchPlaceholder}
                emptyMessage={emptyMessage}
                disabled={disabled}
                items={creatable ? [...flatOptions, creatable] : items}
                groupedItems={groupedItems}
                selectedOptions={selectedOptions}
                inputValue={inputValue}
                inputAria={inputAria}
                search={search}
                onChange={props.onChange}
                onCreate={props.onCreate}
            />
        );
    }

    const selectedOption = selectedOptions[0] ?? null;
    const selectedLabel = optionLabel(selectedOption);
    const inputValue = search.inputFocused ? search.searchValue : selectedLabel;

    return (
        <SingleSelectCombobox
            ui={ui}
            id={id}
            placeholder={placeholder}
            emptyMessage={emptyMessage}
            disabled={disabled}
            items={items}
            groupedItems={groupedItems}
            selectedOption={selectedOption}
            inputValue={inputValue}
            richSelection={richSelection}
            inputAria={inputAria}
            search={search}
            onChange={props.onChange}
            onSelectLabel={search.setSearchValue}
        />
    );
}
