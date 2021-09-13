import { CSSObject } from '@emotion/serialize';
import { Field as FormikField, FieldProps } from 'formik';
import React, { forwardRef } from 'react';
import Select, { ContainerProps, ControlProps, GroupProps, IndicatorContainerProps, IndicatorProps, InputProps, MenuListComponentProps, MenuProps, MultiValueProps, OptionProps, PlaceholderProps, SingleValueProps, StylesConfig, ValueContainerProps } from 'react-select';
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
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
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

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    control: (base: CSSObject, props: ControlProps<T, any, any>): CSSObject => {
        return {
            ...base,
            height: '3rem',
            /* paddingTop: '0.75rem',
            paddingBottom: '0.75rem',
            paddingLeft: '4rem',
            paddingRight: '4rem', */
            background: theme`colors.neutral.600`,
            borderColor: theme`colors.neutral.500`,
            borderWidth: '2px',
            color: theme`colors.neutral.200`,
            cursor: 'pointer',
            // boxShadow: props.isFocused ? '0 0 0 2px #2684ff' : undefined,

            ':hover': {
                borderColor: !props.isFocused ? theme`colors.neutral.400` : theme`colors.neutral.500`,
            },
        };
    },

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    dropdownIndicator: (base: CSSObject, props: IndicatorProps<T, any, any>): CSSObject => {
        return {
            ...base,
            color: props.isFocused ? theme`colors.neutral.300` : theme`colors.neutral.400`,
            // TODO: find better alternative for `isFocused` so this only triggers when the dropdown is visible.
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
            // width: '0',
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

interface Props {
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
    isValidNewOption?: ((
        inputValue: string,
        value: ValueType<any, boolean>,
        options: ReadonlyArray<any>,
    ) => boolean) | undefined;

    className?: string;
}

const SelectField = forwardRef<HTMLElement, Props>(({ id, name, label, description, validate, className, isMulti, isCreatable, ...props }, ref) => {
    const { options } = props;

    const onChange = (options: Option | Option[], name: string, setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void) => {
        if (isMulti) {
            setFieldValue(name, (options as Option[]).map(o => o.value));
            return;
        }

        setFieldValue(name, (options as Option).value);
    };

    return (
        <FormikField innerRef={ref} name={name} validate={validate}>
            {
                ({ field, form: { errors, touched, setFieldValue } }: FieldProps) => (
                    <div className={className}>
                        {label &&
                        <Label htmlFor={id}>{label}</Label>
                        }
                        {isCreatable ?
                            <Creatable
                                {...field}
                                {...props}
                                styles={SelectStyle}
                                options={options}
                                value={(options ? options.find(o => o.value === field.value) : '') as any}
                                onChange={o => onChange(o, name, setFieldValue)}
                                isMulti={isMulti}
                            />
                            :
                            <Select
                                {...field}
                                {...props}
                                styles={SelectStyle}
                                options={options}
                                value={(options ? options.find(o => o.value === field.value) : '') as any}
                                onChange={o => onChange(o, name, setFieldValue)}
                                isMulti={isMulti}
                            />
                        }
                        {touched[field.name] && errors[field.name] ?
                            <p css={tw`text-red-200 text-xs mt-1`}>
                                {(errors[field.name] as string).charAt(0).toUpperCase() + (errors[field.name] as string).slice(1)}
                            </p>
                            :
                            description ? <p css={tw`text-neutral-400 text-xs mt-1`}>{description}</p> : null
                        }
                    </div>
                )
            }
        </FormikField>
    );
});
SelectField.displayName = 'SelectField';

export default SelectField;
