import { queryOptions, useQuery, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import i18next from 'i18next';
import { toast } from 'sonner';

import { invalidateGeneratedOperations, removeQueriesWhenUnobserved } from '@/api/mutationUtils';
import { fetchAllPages, MAX_PER_PAGE } from '@/api/pagination';
import { withQueryOverrides, type ResourceQueryOverrides } from '@/api/resourceQuery';
import {
    adminCreateEggMutation,
    adminCreateEggVariableMutation,
    adminDeleteEggMutation,
    adminDeleteEggVariableMutation,
    adminExportEggOptions,
    adminGetEggOptions,
    adminGetEggQueryKey,
    adminExportEggQueryKey,
    adminImportEggMutation,
    adminListEggVariablesOptions,
    adminListEggVariablesQueryKey,
    adminUpdateEggFromImportMutation,
    adminUpdateEggInstallScriptMutation,
    adminUpdateEggMutation,
    adminUpdateEggVariableMutation,
    adminListEggsOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListEggs,
    type AdminCreateEggData,
    type AdminCreateEggVariableData,
    type AdminDeleteEggData,
    type AdminDeleteEggVariableData,
    type AdminEggResource,
    type AdminEggVariableResource,
    type AdminImportEggData,
    type AdminUpdateEggData,
    type AdminUpdateEggFromImportData,
    type AdminUpdateEggInstallScriptData,
    type AdminUpdateEggVariableData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type AdminEgg = AdminEggResource;
export type AdminEggVariable = AdminEggVariableResource;
export type AdminEggListItem = AdminEggResource;
export type EggConfigurationBody = AdminCreateEggData['body'];
export type EggScriptBody = AdminUpdateEggInstallScriptData['body'];
export type EggImportBody = AdminUpdateEggFromImportData['body'];
export type EggVariableBody = AdminCreateEggVariableData['body'];

export const createAdminEggInput = (body: EggConfigurationBody): Options<AdminCreateEggData> => ({ body });

export const importAdminEggInput = (body: AdminImportEggData['body']): Options<AdminImportEggData> => ({ body });

export const updateAdminEggInput = (eggId: number, body: AdminUpdateEggData['body']): Options<AdminUpdateEggData> => ({
    path: { egg_id: eggId },
    body,
});

export const updateAdminEggScriptInput = (
    eggId: number,
    body: EggScriptBody
): Options<AdminUpdateEggInstallScriptData> => ({
    path: { egg_id: eggId },
    body,
});

export const updateAdminEggFromFileInput = (
    eggId: number,
    body: EggImportBody
): Options<AdminUpdateEggFromImportData> => ({
    path: { egg_id: eggId },
    body,
});

export const deleteAdminEggInput = (eggId: number, name?: string): Options<AdminDeleteEggData> => ({
    path: { egg_id: eggId },
    meta: name ? { name } : undefined,
});

export const createAdminEggVariableInput = (
    eggId: number,
    body: EggVariableBody
): Options<AdminCreateEggVariableData> => ({
    path: { egg_id: eggId },
    body,
});

export const updateAdminEggVariableInput = (
    eggId: number,
    variableId: number,
    body: AdminUpdateEggVariableData['body']
): Options<AdminUpdateEggVariableData> => ({
    path: { egg_id: eggId, variable_id: variableId },
    body,
});

export const deleteAdminEggVariableInput = (
    eggId: number,
    variableId: number,
    name?: string
): Options<AdminDeleteEggVariableData> => ({
    path: { egg_id: eggId, variable_id: variableId },
    meta: name ? { name } : undefined,
});

const adminEggsInput = { query: { sort: 'id', per_page: MAX_PER_PAGE } } as const;

const adminEggInput = (eggId: number): Parameters<typeof adminGetEggOptions>[0] => ({
    path: { egg_id: eggId },
    query: { include: 'tags' },
});

const adminEggVariablesInput = (eggId: number): Parameters<typeof adminListEggVariablesOptions>[0] => ({
    path: { egg_id: eggId },
});

export const adminEggDetailKey = (eggId: number) => adminGetEggQueryKey({ path: { egg_id: eggId } });
const adminEggVariableListQueryKey = (eggId: number) => adminListEggVariablesQueryKey(adminEggVariablesInput(eggId));

// The complete egg list: pickers and the eggs page need every egg, so all pages are loaded.
export const adminEggsQueryOptions = () =>
    queryOptions({
        ...adminListEggsOptions(adminEggsInput),
        queryFn: ({ signal }) =>
            fetchAllPages(
                async (page) => (await adminListEggs({ query: { ...adminEggsInput.query, page }, signal })).data,
                signal
            ),
    });

export const adminEggQueryOptions = (eggId: number) => ({
    ...adminGetEggOptions(adminEggInput(eggId)),
    enabled: Number.isInteger(eggId) && eggId > 0,
});

export const adminEggVariablesQueryOptions = (eggId: number) => ({
    ...adminListEggVariablesOptions(adminEggVariablesInput(eggId)),
    enabled: Number.isInteger(eggId) && eggId > 0,
});

const adminEggExportQueryOptions = (eggId: number) => ({
    ...adminExportEggOptions({ path: { egg_id: eggId } }),
    enabled: false,
});

export const useAdminEggs = () => useQuery(adminEggsQueryOptions());

export const useAdminEgg = (eggId: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminEggQueryOptions(eggId), options));

export const useAdminEggVariables = (eggId: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminEggVariablesQueryOptions(eggId), options));

const adminEggListOperations = ['adminListEggs'];

const invalidateAdminEggVariables = (queryClient: QueryClient, eggId: number) =>
    Promise.all([
        queryClient.invalidateQueries({ queryKey: adminEggVariableListQueryKey(eggId) }),
        queryClient.invalidateQueries({ queryKey: adminEggDetailKey(eggId) }),
    ]);

export const useImportAdminEgg = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminImportEggMutation(),
        onSuccess: async (egg) => {
            await invalidateGeneratedOperations(queryClient, adminEggListOperations);
            toast.success(i18next.t('Egg imported'), {
                description: i18next.t('{{name}} has been imported from the uploaded file.', {
                    name: egg.attributes.name,
                }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to import egg')),
    });
};

export const useCreateAdminEgg = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminCreateEggMutation(),
        onSuccess: async (egg) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, adminEggListOperations),
                queryClient.invalidateQueries({ queryKey: adminEggDetailKey(egg.attributes.id) }),
            ]);
            toast.success(i18next.t('Egg created'), {
                description: i18next.t('{{name}} has been created.', { name: egg.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to create egg')),
    });
};

export const useUpdateAdminEgg = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateEggMutation(),
        onSuccess: async (egg) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, adminEggListOperations),
                queryClient.invalidateQueries({ queryKey: adminEggDetailKey(egg.attributes.id) }),
            ]);
            toast.success(i18next.t('Egg updated'), {
                description: i18next.t('{{name}} has been updated.', { name: egg.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update egg')),
    });
};

export const useUpdateAdminEggScript = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateEggInstallScriptMutation(),
        onSuccess: async (egg) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, adminEggListOperations),
                queryClient.invalidateQueries({ queryKey: adminEggDetailKey(egg.attributes.id) }),
            ]);
            toast.success(i18next.t('Install script updated'), {
                description: i18next.t('{{name}} has a new install script.', { name: egg.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update install script')),
    });
};

export const useUpdateAdminEggFromFile = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateEggFromImportMutation(),
        onSuccess: async (egg) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, adminEggListOperations),
                queryClient.invalidateQueries({ queryKey: adminEggDetailKey(egg.attributes.id) }),
            ]);
            toast.success(i18next.t('Egg updated'), {
                description: i18next.t('{{name}} has been updated from the uploaded file.', {
                    name: egg.attributes.name,
                }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update egg from file')),
    });
};

export const useExportAdminEgg = (eggId: number) => useQuery(adminEggExportQueryOptions(eggId));

export const useDeleteAdminEgg = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteEggMutation(),
        onSuccess: async (_data, variables) => {
            const eggId = variables.path.egg_id;

            removeQueriesWhenUnobserved(queryClient, { queryKey: adminEggDetailKey(eggId) });
            removeQueriesWhenUnobserved(queryClient, { queryKey: adminEggVariableListQueryKey(eggId) });
            removeQueriesWhenUnobserved(queryClient, { queryKey: adminExportEggQueryKey({ path: { egg_id: eggId } }) });
            await invalidateGeneratedOperations(queryClient, adminEggListOperations);
            const name = variables.meta?.name;

            toast.success(i18next.t('Egg deleted'), {
                description: name
                    ? i18next.t('{{name}} has been deleted.', { name })
                    : i18next.t('The egg has been deleted.'),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to delete egg')),
    });
};

export const useCreateAdminEggVariable = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminCreateEggVariableMutation(),
        onSuccess: async (variable, variables) => {
            await invalidateAdminEggVariables(queryClient, variables.path.egg_id);
            toast.success(i18next.t('Variable created'), {
                description: i18next.t('{{name}} has been created.', { name: variable.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to create variable')),
    });
};

export const useUpdateAdminEggVariable = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateEggVariableMutation(),
        onSuccess: async (variable, variables) => {
            await invalidateAdminEggVariables(queryClient, variables.path.egg_id);
            toast.success(i18next.t('Variable updated'), {
                description: i18next.t('{{name}} has been updated.', { name: variable.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update variable')),
    });
};

export const useDeleteAdminEggVariable = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteEggVariableMutation(),
        onSuccess: async (_data, variables) => {
            await invalidateAdminEggVariables(queryClient, variables.path.egg_id);
            const name = variables.meta?.name;

            toast.success(i18next.t('Variable deleted'), {
                description: name
                    ? i18next.t('{{name}} has been deleted.', { name })
                    : i18next.t('The variable has been deleted.'),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to delete variable')),
    });
};
