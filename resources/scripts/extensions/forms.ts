import { useQuery, useQueryClient } from '@tanstack/react-query';
import { adminExtensionFormsQueryOptions } from '@/api/admin/extensions/queries';
import { getFormExtensions, useExtensionRegistry } from './registry';
import type {
    ExtensionFieldValues,
    ExtensionFormName,
    ExtensionFormResources,
    FormExtensionRegistration,
} from './formTypes';

/**
 * The values of an admin form's `extensions` field: for each extension whose fields were
 * edited, all of its values. A save sends them through `useExtensionPayload()`, which adds
 * every other extension the form shows.
 */
export type ExtensionFormValues = Record<string, ExtensionFieldValues>;

/** The extensions whose fields each admin form shows, as the panel lists them. */
export type ExtensionFormsList = Partial<Record<ExtensionFormName, readonly { id: string; name: string }[]>>;

/** The initial `extensions` value of an admin form: no extension's fields edited yet. */
export const initialExtensionValues = (): ExtensionFormValues => ({});

/** The components extensions registered for an admin form with `forms.extend()`. */
export function useFormExtensions(form: ExtensionFormName): readonly FormExtensionRegistration[] {
    return useExtensionRegistry(() => getFormExtensions(form));
}

/**
 * The extensions the panel lists for each admin form, fetched once some extension drew fields
 * for `form`. Undefined while loading, and when no extension drew any.
 */
export function useExtensionForms(form: ExtensionFormName): ExtensionFormsList | undefined {
    const registrations = useFormExtensions(form);

    return useQuery({ ...adminExtensionFormsQueryOptions(), enabled: registrations.length > 0 }).data?.data;
}

export interface ShownFormExtension {
    id: string;
    name: string;
    component: FormExtensionRegistration['component'];
}

/**
 * The extensions an admin form shows: those the panel lists for it (`forms`, none while it
 * loads) whose bundle drew fields with `forms.extend()`. When editing (`saved` is given), only
 * those whose values the panel returned, so a save cannot write over values it could not read.
 */
export function shownFormExtensions(
    form: ExtensionFormName,
    forms: ExtensionFormsList | undefined,
    registrations: readonly FormExtensionRegistration[],
    saved?: Partial<Record<string, ExtensionFieldValues>>
): ShownFormExtension[] {
    return (forms?.[form] ?? []).flatMap((entry) => {
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
function extensionFormPayload<TName extends ExtensionFormName>(
    form: TName,
    forms: ExtensionFormsList | undefined,
    edits: ExtensionFormValues,
    resource?: ExtensionFormResources[TName]
): ExtensionFormValues {
    const saved = resource ? (resource.attributes.extensions ?? {}) : undefined;

    return Object.fromEntries(
        shownFormExtensions(form, forms, getFormExtensions(form), saved).map(({ id }) => [
            id,
            { ...saved?.[id], ...edits[id] },
        ])
    );
}

/**
 * Builds the `extensions` an admin form saves from the list `<ExtensionFormFields>` fetched,
 * read when the form submits, so it sends exactly the extensions the form showed.
 */
export function useExtensionPayload<TName extends ExtensionFormName>(form: TName) {
    const queryClient = useQueryClient();
    const forms = () => queryClient.getQueryData(adminExtensionFormsQueryOptions().queryKey)?.data;

    return {
        /** The form's `extensions` edits as the payload to save. */
        extensionFormPayload: (edits: ExtensionFormValues, resource?: ExtensionFormResources[TName]) =>
            extensionFormPayload(form, forms(), edits, resource),
        /** A form's values with `extensions` replaced by `extensionFormPayload()` of them. */
        withExtensionPayload: <TValues extends { extensions: ExtensionFormValues }>(
            values: TValues,
            resource?: ExtensionFormResources[TName]
        ): TValues => ({ ...values, extensions: extensionFormPayload(form, forms(), values.extensions, resource) }),
    };
}
