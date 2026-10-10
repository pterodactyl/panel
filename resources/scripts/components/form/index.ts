import { createFormHook, type AppFieldExtendedReactFormApi } from '@tanstack/react-form';
import type { FormAsyncValidateOrFn, FormValidateOrFn } from '@tanstack/form-core';
import { fieldContext, formContext } from './context';
import {
    TextField,
    PasswordField,
    TextAreaField,
    NumberField,
    SelectField,
    MultiSelectField,
    SwitchField,
    CheckboxField,
} from './fields';
import { SubmitButton } from './SubmitButton';

const fieldComponents = {
    TextField,
    PasswordField,
    TextAreaField,
    NumberField,
    SelectField,
    MultiSelectField,
    SwitchField,
    CheckboxField,
};

const formComponents = { SubmitButton };

export const { useAppForm } = createFormHook({
    fieldContext,
    formContext,
    fieldComponents,
    formComponents,
});

export { default as Form } from './Form';
export { useFormContext, useFieldContext } from './context';
export type { SelectOption } from './fields';

type SyncValidator<TFormData> = FormValidateOrFn<TFormData> | undefined;
type AsyncValidator<TFormData> = FormAsyncValidateOrFn<TFormData> | undefined;

/** Types a `form` prop for a sub-component that renders `<form.AppField>` of its parent's form. */
export type AppForm<TFormData> = AppFieldExtendedReactFormApi<
    TFormData,
    SyncValidator<TFormData>,
    SyncValidator<TFormData>,
    AsyncValidator<TFormData>,
    SyncValidator<TFormData>,
    AsyncValidator<TFormData>,
    SyncValidator<TFormData>,
    AsyncValidator<TFormData>,
    SyncValidator<TFormData>,
    AsyncValidator<TFormData>,
    AsyncValidator<TFormData>,
    unknown,
    typeof fieldComponents,
    typeof formComponents
>;
