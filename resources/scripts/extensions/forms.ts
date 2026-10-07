import { useCallback, useSyncExternalStore } from 'react';
import { getFormExtensions, subscribeExtensionRegistry } from './registry';
import type { ExtensionFieldValues, ExtensionFormName, FormExtensionRegistration } from './formTypes';

/**
 * The values of an admin form's `extensions` field: for each extension whose fields were
 * edited, all of its values. Extensions left untouched are left out, so a save never sends
 * values it did not change.
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
