import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { ListSort, QueryBuilderParams } from '@/api/queryParameters';

import { listQuery, listSorts } from '@/api/queryParameters';
import { allPagesQueryOptions, listItems, MAX_PER_PAGE } from '@/api/pagination';
import { withQueryOverrides, type ResourceQueryOverrides } from '@/api/resourceQuery';
import {
    invalidateGeneratedOperations,
    removeQueriesWhenUnobserved,
    resourceMutationMessages,
} from '@/api/mutationUtils';
import {
    adminCreateLocationMutation,
    adminDeleteLocationMutation,
    adminGetLocationOptions,
    adminGetLocationQueryKey,
    adminListLocationsOptions,
    adminListLocationsQueryKey,
    adminUpdateLocationMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListLocations,
    type AdminCreateLocationData,
    type AdminDeleteLocationData,
    type AdminListLocationsData,
    type AdminLocationResource,
    type AdminUpdateLocationData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';
import type { ExtensionFormValues } from '@/extensions/forms';

type LocationsFilters = 'short' | 'long';
export type LocationListSort = ListSort<'short' | 'created_at'>;
type LocationSorts = 'id' | 'short' | 'created_at';

export type AdminLocationsQueryParams = QueryBuilderParams<LocationsFilters, LocationSorts>;
export type LocationValues = AdminCreateLocationData['body'] & { extensions: ExtensionFormValues };
export type AdminLocation = AdminLocationResource;

export const createAdminLocationInput = (values: LocationValues): Options<AdminCreateLocationData> => ({
    body: values,
});

export const updateAdminLocationInput = (id: number, values: LocationValues): Options<AdminUpdateLocationData> => ({
    path: { id },
    body: values,
});

export const deleteAdminLocationInput = (id: number, short?: string): Options<AdminDeleteLocationData> => ({
    path: { location_id: id },
    meta: short ? { short } : undefined,
});

const locationListSortFields = ['short', 'created_at'] as const;

export const adminLocationListSorts = (sort?: LocationListSort): AdminLocationsQueryParams['sorts'] =>
    listSorts(locationListSortFields, sort);

export const adminLocationListQueryParams = (
    page: number,
    filter: string,
    sort?: LocationListSort
): AdminLocationsQueryParams => ({
    page,
    filters: { short: filter },
    sorts: adminLocationListSorts(sort),
});

export const adminLocationsQueryOptions = (params: AdminLocationsQueryParams = {}) =>
    adminListLocationsOptions({ query: listQuery(params) });

type AdminLocationsQueryOptions = Pick<ReturnType<typeof adminLocationsQueryOptions>, 'placeholderData'> & {
    enabled?: boolean;
};

const allAdminLocationsInput = {
    query: { sort: 'id', per_page: MAX_PER_PAGE },
} satisfies Options<AdminListLocationsData>;

export const allAdminLocationsQueryOptions = () =>
    allPagesQueryOptions(adminListLocationsQueryKey(allAdminLocationsInput), (page, signal) =>
        adminListLocations({ query: { ...allAdminLocationsInput.query, page }, signal }).then(({ data }) => data)
    );

const adminLocationInput = (id: number): Parameters<typeof adminGetLocationOptions>[0] => ({
    path: { location_id: id },
    query: { include: 'nodes' },
});

export const adminLocationDetailKey = (id: number) => adminGetLocationQueryKey({ path: { location_id: id } });

export const adminLocationQueryOptions = (id: number) => ({
    ...adminGetLocationOptions(adminLocationInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

export const useAdminLocations = (params: AdminLocationsQueryParams = {}, options?: AdminLocationsQueryOptions) =>
    useQuery({ ...adminLocationsQueryOptions(params), ...options });

export const useAllAdminLocations = () => useQuery({ ...allAdminLocationsQueryOptions(), select: listItems });

export const useAdminLocation = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminLocationQueryOptions(id), options));

export const useCreateAdminLocation = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminCreateLocationMutation(),
        onSuccess: async (location) => {
            await invalidateGeneratedOperations(queryClient, ['adminListLocations']);
            const messages = resourceMutationMessages('location', 'create', location.attributes.short);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('location', 'create').errorTitle),
    });
};

export const useUpdateAdminLocation = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateLocationMutation(),
        onSuccess: async (location, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListLocations']),
                queryClient.invalidateQueries({ queryKey: adminLocationDetailKey(variables.path.id) }),
            ]);
            const messages = resourceMutationMessages('location', 'update', location.attributes.short);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('location', 'update').errorTitle),
    });
};

export const useDeleteAdminLocation = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteLocationMutation(),
        onSuccess: async (_data, variables) => {
            removeQueriesWhenUnobserved(queryClient, { queryKey: adminLocationDetailKey(variables.path.location_id) });
            await invalidateGeneratedOperations(queryClient, ['adminListLocations']);
            const name = variables.meta?.short;
            const messages = resourceMutationMessages('location', 'delete', name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('location', 'delete').errorTitle),
    });
};
