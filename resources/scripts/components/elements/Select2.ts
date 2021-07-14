import { CSSObject } from '@emotion/serialize';
import { ContainerProps, ControlProps, InputProps, MenuProps, MultiValueProps, OptionProps, PlaceholderProps, SingleValueProps, StylesConfig, ValueContainerProps } from 'react-select';
import { theme } from 'twin.macro';

type T = any;

export const SelectStyle: StylesConfig<T, any, any> = {
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
            height: '2.75rem',
            /* paddingTop: '0.75rem',
            paddingBottom: '0.75rem',
            paddingLeft: '4rem',
            paddingRight: '4rem', */
            background: theme`colors.neutral.600`,
            borderColor: theme`colors.neutral.500`,
            borderWidth: '2px',
            color: theme`colors.neutral.200`,
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
    menu: (base: CSSObject, props: MenuProps<T, any, any>): CSSObject => {
        return {
            ...base,
            background: theme`colors.neutral.900`,
            color: theme`colors.neutral.200`,
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
