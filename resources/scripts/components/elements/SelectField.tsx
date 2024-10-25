import { CSSObject } from '@emotion/serialize';
import { Field as FormikField, FieldProps } from 'formik';
import { forwardRef } from 'react';
import Select, {
    ContainerProps,
    ControlProps,
    GroupProps,
    IndicatorContainerProps,
    IndicatorProps,
    InputProps,
    MenuListComponentProps,
    MenuProps,
    MultiValueProps,
    OptionProps,
    PlaceholderProps,
    SingleValueProps,
    StylesConfig,
    ValueContainerProps,
} from 'react-select';
import Async from 'react-select/async';
import Creatable from 'react-select/creatable';
import tw, { theme } from 'twin.macro';
import Label from '@/components/elements/Label';
import { ValueType } from 'react-select/src/types';
import { GroupHeadingProps } from 'react-select/src/components/Group';
import { MenuPortalProps, NoticeProps } from 'react-select/src/components/Menu';
import { LoadingIndicatorProps } from 'react-select/src/components/indicators';
import { MultiValueRemoveProps } from 'react-select/src/components/MultiValue';

type T = any;

export const SelectStyle: StylesConfig<T, any, any> = {
    clearIndicator: (base: CSSObject, props: IndicatorProps<T, any, any>): CSSObject => {
        return {
            ...base,
            color: props.isFocused ? theme`colors.neutral.300` : theme`colors.neutral.400`,

            ':hover': {
                color: theme`colors.neutral.100`,
            },
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    container: (base: CSSObject, props: ContainerProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    control: (base: CSSObject, props: ControlProps<T, any, any>): CSSObject => {
        return {
            ...base,
            height: '3rem',
            background: theme`colors.neutral.600`,
            borderColor: !props.isFocused ? theme`colors.neutral.500` : theme`colors.primary.300`,
            borderWidth: '2px',
            color: theme`colors.neutral.200`,
            cursor: 'pointer',
            boxShadow: props.isFocused
                ? 'rgb(255, 255, 255) 0px 0px 0px 0px, rgba(36, 135, 235, 0.5) 0px 0px 0px 2px, rgba(0, 0, 0, 0.1) 0px 4px 6px -1px, rgba(0, 0, 0, 0.06) 0px 2px 4px -1px'
                : undefined,

            ':hover': {
                borderColor: !props.isFocused ? theme`colors.neutral.400` : theme`colors.primary.300`,
            },
        };
    },

    dropdownIndicator: (base: CSSObject, props: IndicatorProps<T, any, any>): CSSObject => {
        return {
            ...base,
            color: props.isFocused ? theme`colors.neutral.300` : theme`colors.neutral.400`,
            transform: props.isFocused ? 'rotate(180deg)' : undefined,

            ':hover': {
                color: theme`colors.neutral.300`,
            },
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    group: (base: CSSObject, props: GroupProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    groupHeading: (base: CSSObject, props: GroupHeadingProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    indicatorsContainer: (base: CSSObject, props: IndicatorContainerProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    indicatorSeparator: (base: CSSObject, props: IndicatorProps<T, any, any>): CSSObject => {
        return {
            ...base,
            backgroundColor: theme`colors.neutral.500`,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    input: (base: CSSObject, props: InputProps): CSSObject => {
        return {
            ...base,
            color: theme`colors.neutral.200`,
            fontSize: '0.875rem',
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    loadingIndicator: (base: CSSObject, props: LoadingIndicatorProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    loadingMessage: (base: CSSObject, props: NoticeProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    menu: (base: CSSObject, props: MenuProps<T, any, any>): CSSObject => {
        return {
            ...base,
            background: theme`colors.neutral.900`,
            color: theme`colors.neutral.200`,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    menuList: (base: CSSObject, props: MenuListComponentProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    menuPortal: (base: CSSObject, props: MenuPortalProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    multiValue: (base: CSSObject, props: MultiValueProps<T, any>): CSSObject => {
        return {
            ...base,
            background: theme`colors.neutral.900`,
            color: theme`colors.neutral.200`,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    multiValueLabel: (base: CSSObject, props: MultiValueProps<T, any>): CSSObject => {
        return {
            ...base,
            color: theme`colors.neutral.200`,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    multiValueRemove: (base: CSSObject, props: MultiValueRemoveProps<T, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    noOptionsMessage: (base: CSSObject, props: NoticeProps<T, any, any>): CSSObject => {
        return {
            ...base,
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    option: (base: CSSObject, props: OptionProps<T, any, any>): CSSObject => {
        return {
            ...base,
            background: theme`colors.neutral.900`,

            ':hover': {
                background: theme`colors.neutral.700`,
                cursor: 'pointer',
            },
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    placeholder: (base: CSSObject, props: PlaceholderProps<T, any, any>): CSSObject => {
        return {
            ...base,
            color: theme`colors.neutral.300`,
            fontSize: '0.875rem',
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    singleValue: (base: CSSObject, props: SingleValueProps<T, any>): CSSObject => {
        return {
            ...base,
            color: '#00000',
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    valueContainer: (base: CSSObject, props: ValueContainerProps<T, any>): CSSObject => {
        return {
            ...base,
        };
    },
};

export interface Option {
    value: string;
    label: string;
}

interface SelectFieldProps {
    id?: string;
    name: string;
    label?: string;
    description?: string;
    placeholder?: string;
    validate?: (value: any) => undefined | string | Promise<any>;

    options: Array<Option>;

    isMulti?: boolean;
    isSearchable?: boolean;

    isCreatable?: boolean;
    isValidNewOption?:
        | ((inputValue: string, value: ValueType<any, boolean>, options: ReadonlyArray<any>) => boolean)
        | undefined;

    className?: string;
}

const SelectField = forwardRef<HTMLElement, SelectFieldProps>(function Select2(
    { id, name, label, description, validate, className, isMulti, isCreatable, ...props },
    ref,
) {
    const { options } = props;

    const onChange = (
        options: Option | Option[],
        name: string,
        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
    ) => {
        if (isMulti) {
            setFieldValue(
                name,
                (options as Option[]).map(o => o.value),
            );
            return;
        }

        setFieldValue(name, (options as Option).value);
    };

    return (
        <FormikField innerRef={ref} name={name} validate={validate}>
            {({ field, form: { errors, touched, setFieldValue } }: FieldProps) => (
                <div className={className}>
                    {label && <Label htmlFor={id}>{label}</Label>}
                    {isCreatable ? (
                        <Creatable
                            {...field}
                            {...props}
                            styles={SelectStyle}
                            options={options}
                            value={(options ? options.find(o => o.value === field.value) : '') as any}
                            onChange={o => onChange(o, name, setFieldValue)}
                            isMulti={isMulti}
                        />
                    ) : (
                        <Select
                            {...field}
                            {...props}
                            styles={SelectStyle}
                            options={options}
                            value={(options ? options.find(o => o.value === field.value) : '') as any}
                            onChange={o => onChange(o, name, setFieldValue)}
                            isMulti={isMulti}
                        />
                    )}
                    {touched[field.name] && errors[field.name] ? (
                        <p css={tw`text-red-200 text-xs mt-1`}>
                            {(errors[field.name] as string).charAt(0).toUpperCase() +
                                (errors[field.name] as string).slice(1)}
                        </p>
                    ) : description ? (
                        <p css={tw`text-neutral-400 text-xs mt-1`}>{description}</p>
                    ) : null}
                </div>
            )}
        </FormikField>
    );
});

interface AsyncSelectFieldProps {
    id?: string;
    name: string;
    label?: string;
    description?: string;
    placeholder?: string;
    validate?: (value: any) => undefined | string | Promise<any>;

    isMulti?: boolean;

    className?: string;

    loadOptions(inputValue: string, callback: (options: Array<Option>) => void): void;
}

const AsyncSelectField = forwardRef<HTMLElement, AsyncSelectFieldProps>(function AsyncSelect2(
    { id, name, label, description, validate, className, isMulti, ...props },
    ref,
) {
    const onChange = (
        options: Option | Option[],
        name: string,
        setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void,
    ) => {
        if (isMulti) {
            setFieldValue(
                name,
                (options as Option[]).map(o => Number(o.value)),
            );
            return;
        }

        setFieldValue(name, Number((options as Option).value));
    };

    return (
        <FormikField innerRef={ref} name={name} validate={validate}>
            {({ field, form: { errors, touched, setFieldValue } }: FieldProps) => (
                <div className={className}>
                    {label && <Label htmlFor={id}>{label}</Label>}
                    <Async
                        {...props}
                        id={id}
                        name={name}
                        styles={SelectStyle}
                        onChange={o => onChange(o, name, setFieldValue)}
                        isMulti={isMulti}
                    />
                    {touched[field.name] && errors[field.name] ? (
                        <p css={tw`text-red-200 text-xs mt-1`}>
                            {(errors[field.name] as string).charAt(0).toUpperCase() +
                                (errors[field.name] as string).slice(1)}
                        </p>
                    ) : description ? (
                        <p css={tw`text-neutral-400 text-xs mt-1`}>{description}</p>
                    ) : null}
                </div>
            )}
        </FormikField>
    );
});

export default SelectField;
export { AsyncSelectField };
