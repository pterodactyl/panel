import type { ChangeEvent } from 'react';
import { firstError } from '@/components/form/errors';
import { capitalize } from '@/lib/strings';

/** What browsers and password managers read from an input, which the engine owns. */
export interface FieldAttributes {
    readonly type?: 'text' | 'email' | 'password';
    readonly autoComplete?: string;
    readonly inputMode?: 'numeric';
    readonly pattern?: string;
    readonly disabled?: boolean;
}
/** Spread onto the input or textarea as is; a theme adds its own id, label and classes. */
export interface FieldProps extends FieldAttributes {
    readonly name: string;
    readonly value: string;
    readonly onChange: (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => void;
    readonly onBlur: () => void;
}
export interface FieldModel {
    readonly fieldProps: FieldProps;
    /** The first validation error, shown in place of the description. */
    readonly error: string | null;
}
/** A form the engine validates and submits; render it in a form whose submit event calls `submit`. */
export interface FormModel<F extends string> {
    readonly fields: { readonly [K in F]: FieldModel };
    readonly submit: () => void;
    readonly canSubmit: boolean;
    readonly submitting: boolean;
}

/** The parts of a bound TanStack field a model reads. */
interface BoundField {
    readonly name: string;
    readonly state: { readonly value: string; readonly meta: { readonly errors: unknown[] } };
    readonly handleChange: (value: string) => void;
    readonly handleBlur: () => void;
}

const noop = () => {};

export function boundField(field: BoundField, attributes: FieldAttributes = {}): FieldModel {
    const error = firstError(field.state.meta.errors);

    return {
        fieldProps: {
            ...attributes,
            name: field.name,
            value: field.state.value,
            onChange: (event) => field.handleChange(event.currentTarget.value),
            onBlur: field.handleBlur,
        },
        error: error ? capitalize(error) : null,
    };
}

/** A field kept in plain state, without validation. */
export function stateField(
    name: string,
    value: string,
    setValue: (value: string) => void,
    attributes: FieldAttributes = {}
): FieldModel {
    return {
        fieldProps: {
            ...attributes,
            name,
            value,
            onChange: (event) => setValue(event.currentTarget.value),
            onBlur: noop,
        },
        error: null,
    };
}

export function fixtureField(
    name: string,
    attributes: FieldAttributes = {},
    value = '',
    error: string | null = null
): FieldModel {
    return { fieldProps: { ...attributes, name, value, onChange: noop, onBlur: noop }, error };
}
