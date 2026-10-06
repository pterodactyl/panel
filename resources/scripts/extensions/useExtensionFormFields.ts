import { useQuery } from '@tanstack/react-query';
import { adminExtensionFormValuesQueryOptions } from '@/api/admin/extensions/queries';
import { getLoadableExtensions } from '@/extensions/registry';
import type { ExtensionFormName, LoadedExtensionFieldValues } from '@/extensions/formFields';

export interface ExtensionFormFieldsState {
    values: LoadedExtensionFieldValues;
    ready: boolean;
    hidden: readonly string[];
}

const noValues: LoadedExtensionFieldValues = {};
const noExtensions: readonly string[] = [];

function extensionsWithFields(name: ExtensionFormName): string[] {
    return (getLoadableExtensions() ?? []).filter((entry) => entry.forms?.includes(name)).map((entry) => entry.id);
}

export function newExtensionFieldValues(name: ExtensionFormName): LoadedExtensionFieldValues {
    return Object.fromEntries(extensionsWithFields(name).map((owner) => [owner, {}]));
}

export function useExtensionFormFields(name: ExtensionFormName, id?: number): ExtensionFormFieldsState {
    const owners = extensionsWithFields(name);
    const enabled = id !== undefined && owners.length > 0;
    const query = useQuery({
        ...adminExtensionFormValuesQueryOptions(name, id ?? 0),
        enabled,
        gcTime: 0,
        retry: false,
    });

    if (!enabled) {
        return { values: noValues, ready: true, hidden: noExtensions };
    }

    const values = query.data ?? noValues;
    return {
        values,
        ready: query.isSuccess || query.isError,
        hidden: owners.filter((owner) => !Object.hasOwn(values, owner)),
    };
}
