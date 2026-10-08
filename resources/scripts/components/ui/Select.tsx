import React from 'react';
import { Combobox } from '@base-ui/react/combobox';
import { Check, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/cn';
import { isNumber, isString } from '@/lib/objects';

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
    'aria-describedby'?: string;
    'aria-invalid'?: boolean;
    onBlur?: React.FocusEventHandler<HTMLInputElement>;
    /** Hands the typed query to the caller and turns off client-side filtering. */
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
}

export type SelectProps = SingleSelectProps | MultiSelectProps;

type ComboboxGroupItem = {
    value: string;
    label: string;
    items: SelectOption[];
};

type WithStringClassName<TProps> = Omit<TProps, 'className'> & { className?: string };

const inputGroupClass = [
    'relative flex items-center w-full rounded-sm border text-sm transition-colors duration-150',
    'bg-input border-input text-foreground hover:border-input data-[popup-open]:border-input',
    'focus-within:shadow-md focus-within:border-primary focus-within:ring-2 focus-within:ring-ring/50',
    'data-[disabled]:opacity-75 data-[disabled]:cursor-default',
].join(' ');
const inputErrorClass =
    'border-destructive text-destructive focus-within:border-destructive focus-within:ring-destructive';
const inputClass = [
    'w-full min-w-0 appearance-none border-0 bg-transparent p-3 pr-10 text-sm text-foreground outline-hidden',
    'placeholder:text-muted-foreground focus:ring-0 focus:outline-hidden disabled:cursor-default',
].join(' ');
const triggerClass =
    'absolute right-0 top-0 flex h-full w-10 items-center justify-center border-0 bg-transparent p-0 text-muted-foreground [&_svg]:h-4 [&_svg]:w-4 [&_svg]:shrink-0';
const positionerClass = 'z-[99999] min-w-[var(--anchor-width)] outline-none';
const popupClass = [
    'bg-card border border-border rounded-sm shadow-lg py-1 text-sm text-foreground',
    'w-[var(--anchor-width)] max-w-[var(--available-width)] max-h-[min(18rem,var(--available-height))]',
].join(' ');
const listClass = 'max-h-72 overflow-y-auto overscroll-contain outline-none';
const itemClass =
    'flex items-center justify-between px-3 py-2 cursor-pointer select-none outline-hidden data-[highlighted]:bg-popover data-[highlighted]:text-foreground data-[selected]:text-accent data-[disabled]:opacity-50 data-[disabled]:cursor-default';
const emptyClass = 'px-3 py-2 text-sm text-muted-foreground empty:p-0';
const groupLabelClass = 'px-3 pt-2 pb-1 text-xs uppercase text-muted-foreground empty:hidden';
const selectionSummaryClass = 'pointer-events-none whitespace-nowrap px-3 py-3 text-sm text-foreground';
const selectionPreviewClass = 'pointer-events-none flex min-w-0 flex-1 items-center px-3 py-2 pr-10 text-sm';

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

const ComboboxInputGroup = ({
    $hasError,
    className,
    ...props
}: { $hasError?: boolean } & WithStringClassName<React.ComponentProps<typeof Combobox.InputGroup>>) => (
    <Combobox.InputGroup className={cn(inputGroupClass, $hasError && inputErrorClass, className)} {...props} />
);

const ComboboxInput = ({ className, ...props }: WithStringClassName<React.ComponentProps<typeof Combobox.Input>>) => (
    <Combobox.Input className={cn(inputClass, className)} {...props} />
);

const ComboboxTrigger = ({
    className,
    ...props
}: WithStringClassName<React.ComponentProps<typeof Combobox.Trigger>>) => (
    <Combobox.Trigger className={cn(triggerClass, className)} {...props} />
);

const ComboboxPositioner = ({
    className,
    ...props
}: WithStringClassName<React.ComponentProps<typeof Combobox.Positioner>>) => (
    <Combobox.Positioner className={cn(positionerClass, className)} {...props} />
);

const ComboboxPopup = ({ className, ...props }: WithStringClassName<React.ComponentProps<typeof Combobox.Popup>>) => (
    <Combobox.Popup className={cn(popupClass, className)} {...props} />
);

const ComboboxList = ({ className, ...props }: WithStringClassName<React.ComponentProps<typeof Combobox.List>>) => (
    <Combobox.List className={cn(listClass, className)} {...props} />
);

const ComboboxItem = ({ className, ...props }: WithStringClassName<React.ComponentProps<typeof Combobox.Item>>) => (
    <Combobox.Item className={cn(itemClass, className)} {...props} />
);

const ComboboxEmpty = ({ className, ...props }: WithStringClassName<React.ComponentProps<typeof Combobox.Empty>>) => (
    <Combobox.Empty className={cn(emptyClass, className)} {...props} />
);

const ComboboxGroupLabel = ({
    className,
    ...props
}: WithStringClassName<React.ComponentProps<typeof Combobox.GroupLabel>>) => (
    <Combobox.GroupLabel className={cn(groupLabelClass, className)} {...props} />
);

const ComboboxSelectionSummary = ({ className, ...props }: React.ComponentProps<'span'>) => (
    <span className={cn(selectionSummaryClass, className)} {...props} />
);

const CheckIcon = () => <Check size={14} strokeWidth={2.5} aria-hidden />;

const ChevronIcon = () => <ChevronDown size={16} aria-hidden />;

const ComboboxOptionItem = ({ option, showIndicator }: { option: SelectOption; showIndicator: boolean }) => (
    <ComboboxItem value={option} disabled={option.disabled}>
        <span className='min-w-0'>{option.label}</span>
        {showIndicator && (
            <Combobox.ItemIndicator>
                <CheckIcon />
            </Combobox.ItemIndicator>
        )}
    </ComboboxItem>
);

const ComboboxOptionsList = ({
    groups,
    showIndicator,
}: {
    groups: ComboboxGroupItem[] | undefined;
    showIndicator: boolean;
}) =>
    groups ? (
        <ComboboxList>
            {(group: ComboboxGroupItem) => (
                <Combobox.Group key={group.value} items={group.items}>
                    <ComboboxGroupLabel>{group.label}</ComboboxGroupLabel>
                    <Combobox.Collection>
                        {(option: SelectOption) => (
                            <ComboboxOptionItem key={optionKey(option)} option={option} showIndicator={showIndicator} />
                        )}
                    </Combobox.Collection>
                </Combobox.Group>
            )}
        </ComboboxList>
    ) : (
        <ComboboxList>
            {(option: SelectOption) => (
                <ComboboxOptionItem key={optionKey(option)} option={option} showIndicator={showIndicator} />
            )}
        </ComboboxList>
    );

const ComboboxPopupContent = ({
    groupedItems,
    emptyMessage,
    showIndicator,
}: {
    groupedItems: ComboboxGroupItem[] | undefined;
    emptyMessage: string;
    showIndicator: boolean;
}) => (
    <Combobox.Portal>
        <ComboboxPositioner sideOffset={4}>
            <ComboboxPopup>
                <ComboboxEmpty>{emptyMessage}</ComboboxEmpty>
                <ComboboxOptionsList groups={groupedItems} showIndicator={showIndicator} />
            </ComboboxPopup>
        </ComboboxPositioner>
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
}: {
    onBlur?: React.FocusEventHandler<HTMLInputElement>;
    onSearchChange?: (value: string) => void;
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
        externalFilter: onSearchChange ? null : undefined,
    };
}

type SelectSearch = ReturnType<typeof useSelectSearch>;

type InputAriaProps = Pick<React.ComponentProps<'input'>, 'aria-describedby' | 'aria-invalid'>;

function MultiSelectCombobox({
    id,
    placeholder,
    searchPlaceholder,
    emptyMessage,
    hasError,
    disabled,
    items,
    groupedItems,
    selectedOptions,
    inputValue,
    inputAria,
    search,
    onChange,
}: {
    id: string | undefined;
    placeholder: string | undefined;
    searchPlaceholder: string;
    emptyMessage: string;
    hasError: boolean | undefined;
    disabled: boolean | undefined;
    items: SelectOption[] | ComboboxGroupItem[];
    groupedItems: ComboboxGroupItem[] | undefined;
    selectedOptions: SelectOption[];
    inputValue: string;
    inputAria: InputAriaProps;
    search: SelectSearch;
    onChange: (value: SelectValue[]) => void;
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
                onChange(value.map((option) => option.value));
                search.resetInputValue();
            }}
            filter={search.externalFilter}
            disabled={disabled}
            itemToStringLabel={optionLabel}
            itemToStringValue={(option) => String(option?.value ?? '')}
            isItemEqualToValue={isSameOption}
            autoHighlight
        >
            <ComboboxInputGroup $hasError={hasError}>
                <Combobox.Value>
                    {(value: SelectOption[]) => (
                        <>
                            {value.length > 0 && (
                                <ComboboxSelectionSummary>{`${value.length} selected`}</ComboboxSelectionSummary>
                            )}
                            <ComboboxInput
                                {...inputAria}
                                id={id}
                                className={value.length > 0 ? 'pl-0' : undefined}
                                placeholder={value.length > 0 ? searchPlaceholder : placeholder}
                                onFocus={search.handleFocus}
                                onBlur={search.handleBlur}
                            />
                        </>
                    )}
                </Combobox.Value>
                <ComboboxTrigger aria-label='Open popup'>
                    <ChevronIcon />
                </ComboboxTrigger>
            </ComboboxInputGroup>
            <ComboboxPopupContent groupedItems={groupedItems} emptyMessage={emptyMessage} showIndicator />
        </Combobox.Root>
    );
}

function SingleSelectCombobox({
    id,
    placeholder,
    emptyMessage,
    hasError,
    disabled,
    items,
    groupedItems,
    selectedOption,
    inputValue,
    richSelection,
    inputFocused,
    inputAria,
    search,
    onChange,
    onSelectLabel,
}: {
    id: string | undefined;
    placeholder: string | undefined;
    emptyMessage: string;
    hasError: boolean | undefined;
    disabled: boolean | undefined;
    items: SelectOption[] | ComboboxGroupItem[];
    groupedItems: ComboboxGroupItem[] | undefined;
    selectedOption: SelectOption | null;
    inputValue: string;
    richSelection: React.ReactNode | null;
    inputFocused: boolean;
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
            <ComboboxInputGroup $hasError={hasError}>
                {richSelection && (
                    <span className={cn(selectionPreviewClass, inputFocused && 'invisible')}>{richSelection}</span>
                )}
                <ComboboxInput
                    {...inputAria}
                    id={id}
                    className={cn(
                        richSelection && 'absolute inset-0 h-full',
                        richSelection && !inputFocused && 'text-transparent'
                    )}
                    placeholder={placeholder}
                    onFocus={search.handleFocus}
                    onBlur={search.handleBlur}
                />
                <ComboboxTrigger aria-label='Open popup'>
                    <ChevronIcon />
                </ComboboxTrigger>
            </ComboboxInputGroup>
            <ComboboxPopupContent groupedItems={groupedItems} emptyMessage={emptyMessage} showIndicator={false} />
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
        onBlur,
        onSearchChange,
    } = props;
    const flatOptions = getOptions(options, groups);
    const groupedItems = getGroups(groups);
    const items = groupedItems ?? flatOptions;
    const search = useSelectSearch({ onBlur, onSearchChange });
    const selectedValues = props.multiple ? props.value : singleSelectedValues(props.value);
    const selectedOptions = useSelectedOptions(flatOptions, selectedValues);
    const inputAria: InputAriaProps = {
        'aria-describedby': props['aria-describedby'],
        'aria-invalid': props['aria-invalid'],
    };

    if (props.multiple) {
        const inputValue = search.inputFocused ? search.searchValue : '';

        return (
            <MultiSelectCombobox
                id={id}
                placeholder={placeholder}
                searchPlaceholder={searchPlaceholder}
                emptyMessage={emptyMessage}
                hasError={hasError}
                disabled={disabled}
                items={items}
                groupedItems={groupedItems}
                selectedOptions={selectedOptions}
                inputValue={inputValue}
                inputAria={inputAria}
                search={search}
                onChange={props.onChange}
            />
        );
    }

    const selectedOption = selectedOptions[0] ?? null;
    const selectedLabel = optionLabel(selectedOption);
    const richSelection = resolveRichSelection(selectedOption);
    const inputValue = search.inputFocused ? search.searchValue : selectedLabel;

    return (
        <SingleSelectCombobox
            id={id}
            placeholder={placeholder}
            emptyMessage={emptyMessage}
            hasError={hasError}
            disabled={disabled}
            items={items}
            groupedItems={groupedItems}
            selectedOption={selectedOption}
            inputValue={inputValue}
            richSelection={richSelection}
            inputFocused={search.inputFocused}
            inputAria={inputAria}
            search={search}
            onChange={props.onChange}
            onSelectLabel={search.setSearchValue}
        />
    );
}
