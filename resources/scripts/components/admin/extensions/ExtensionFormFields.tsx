import { useState } from 'react';
import { useStore } from '@tanstack/react-form';
import { httpValidationErrors } from '@/api/http';
import Button from '@/components/elements/Button';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { useFieldContext } from '@/components/form';
import ExtensionMount from '@/extensions/ExtensionMount';
import {
    shownFormExtensions,
    useExtensionForms,
    useFormExtensions,
    type ExtensionFormValues,
} from '@/extensions/forms';
import type {
    ExtensionField,
    ExtensionFieldValue,
    ExtensionFieldValues,
    ExtensionFormName,
    ExtensionFormResources,
} from '@/extensions/formTypes';
import { cn } from '@/lib/cn';

interface Props<TName extends ExtensionFormName> {
    form: TName;
    mode: 'create' | 'edit';
    /** The saved resource, when editing. */
    resource?: ExtensionFormResources[TName];
    /** The error of the last save: the panel's messages for extension fields show on them. */
    error?: unknown;
    /** Draws each extension in its own card, for pages laid out in cards. */
    boxed?: boolean;
    /** Adds a submit button to each card, for pages whose cards each have one. */
    submitLabel?: string;
    className?: string;
}

/**
 * The fields extensions draw in an admin form with `forms.extend()`. Render it inside
 * `<form.AppField name={'extensions'}>` and submit the form's values through
 * `useExtensionPayload()`, so every extension shown here is validated and saved with the
 * form. When editing, an extension whose values the panel did not return (it failed to read
 * them, or the signed-in admin may not see them) is left out, so a save cannot write over them.
 */
export default function ExtensionFormFields<TName extends ExtensionFormName>({
    form,
    mode,
    resource,
    error,
    boxed = false,
    submitLabel,
    className,
}: Props<TName>) {
    const field = useFieldContext<ExtensionFormValues>();
    const isSubmitting = useStore(field.form.store, (state) => state.isSubmitting);
    const registrations = useFormExtensions(form);
    // Nothing shows until the panel's list of the form's extensions arrives.
    const forms = useExtensionForms(form);
    // Fields changed since `error` arrived no longer show its message.
    const [edited, setEdited] = useState<{ error: unknown; paths: readonly string[] }>({ error: undefined, paths: [] });
    const editedPaths = edited.error === error ? edited.paths : [];

    const saved = resource?.attributes.extensions ?? {};
    const extensions = shownFormExtensions(form, forms, registrations, mode === 'create' ? undefined : saved);

    if (extensions.length === 0) {
        return null;
    }

    const messages = httpValidationErrors(error);
    const pending = field.state.value ?? {};
    const valuesOf = (extension: string): ExtensionFieldValues => ({ ...saved[extension], ...pending[extension] });
    const bind =
        (extension: string) =>
        (name: string): ExtensionField<ExtensionFieldValue> => {
            const path = `extensions.${extension}.${name}`;

            return {
                id: `${form}-${extension}-${name}`.replaceAll(/[^\w-]/g, '-'),
                name: path,
                value: valuesOf(extension)[name],
                setValue: (value) => {
                    setEdited((current) => ({
                        error,
                        paths: [...(current.error === error ? current.paths : []), path],
                    }));
                    // An extension's values are sent whole once any of them changes.
                    field.handleChange((current = {}) => ({
                        ...current,
                        [extension]: { ...saved[extension], ...current[extension], [name]: value },
                    }));
                },
                error: editedPaths.includes(path) ? undefined : messages[path],
            };
        };

    return (
        <div className={cn('space-y-4', className)}>
            {extensions.map(({ id, name, component: Component }) => {
                const fields = (
                    <ExtensionMount extensionId={id} context={`form "${form}"`} resetKey={form} isSlot>
                        <Component form={form} mode={mode} resource={resource} values={valuesOf(id)} field={bind(id)} />
                    </ExtensionMount>
                );

                return boxed ? (
                    <TitledGreyBox key={id} title={name}>
                        {fields}
                        {submitLabel && (
                            <div className='mt-6 flex justify-end'>
                                <Button type='submit' isLoading={isSubmitting}>
                                    {submitLabel}
                                </Button>
                            </div>
                        )}
                    </TitledGreyBox>
                ) : (
                    <section key={id} aria-label={name} className='border-t border-border pt-4'>
                        <h3 className='mb-3 text-xs font-medium uppercase tracking-wide text-muted-foreground'>
                            {name}
                        </h3>
                        {fields}
                    </section>
                );
            })}
        </div>
    );
}
