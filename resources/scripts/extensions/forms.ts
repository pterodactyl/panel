import { useCallback, useSyncExternalStore } from 'react';
import { getBootstrapExtensionForms } from '@/bootstrap';
import { getFormExtensions, subscribeExtensionRegistry } from './registry';
import type {
    ExtensionFieldValues,
    ExtensionFormName,
    ExtensionFormResources,
    FormExtensionRegistration,
} from './formTypes';

/**
 * The values of an admin form's `extensions` field: for each extension whose fields were
 * edited, all of its values. A save sends `withExtensionPayload()` of the form's values,
 * which adds every other extension the form shows.
 */
export type ExtensionFormValues = Record<string, ExtensionFieldValues>;

/** The initial `extensions` value of an admin form: no extension's fields edited yet. */
export const initialExtensionValues = (): ExtensionFormValues => ({});

/** The components extensions registered for an admin form with `forms.extend()`. */
export function useFormExtensions(form: ExtensionFormName): readonly FormExtensionRegistration[] {
    const subscribe = useCallback((changed: () => void) => subscribeExtensionRegistry(`form:${form}`, changed), [form]);
    const snapshot = useCallback(() => getFormExtensions(form), [form]);

    return useSyncExternalStore(subscribe, snapshot);
}

export interface ShownFormExtension {
    id: string;
    name: string;
    component: FormExtensionRegistration['component'];
}

/**
 * The extensions an admin form shows: those the panel lists for it whose bundle drew fields
 * with `forms.extend()`. When editing (`saved` is given), only those whose values the panel
 * returned, so a save cannot write over values it could not read.
 */
export function shownFormExtensions(
    form: ExtensionFormName,
    registrations: readonly FormExtensionRegistration[],
    saved?: Partial<Record<string, ExtensionFieldValues>>
): ShownFormExtension[] {
    return (getBootstrapExtensionForms()[form] ?? []).flatMap((entry) => {
        const registration = registrations.find((candidate) => candidate.extensionId === entry.id);

        return registration && (!saved || saved[entry.id])
            ? [{ id: entry.id, name: entry.name, component: registration.component }]
            : [];
    });
}

/**
 * The `extensions` an admin form saves: every extension the form shows, its saved values with
 * the edits applied. Sending each one, edited or not, runs all of its rules on every save, so
 * a `required` field holds like the core fields beside it. Pass the resource when editing.
 */
export function extensionFormPayload<TName extends ExtensionFormName>(
    form: TName,
    edits: ExtensionFormValues,
    resource?: ExtensionFormResources[TName]
): ExtensionFormValues {
    const saved = resource && (resource.attributes.extensions ?? {});

    return Object.fromEntries(
        shownFormExtensions(form, getFormExtensions(form), saved).map(({ id }) => [
            id,
            { ...saved?.[id], ...edits[id] },
        ])
    );
}

/** A form's values with `extensions` replaced by `extensionFormPayload()` of them. */
export function withExtensionPayload<
    TName extends ExtensionFormName,
    TValues extends { extensions: ExtensionFormValues },
>(form: TName, values: TValues, resource?: ExtensionFormResources[TName]): TValues {
    return { ...values, extensions: extensionFormPayload(form, values.extensions, resource) };
}
