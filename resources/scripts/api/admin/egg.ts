import { Model, UUID, WithRelationships, withRelationships } from '@/api/admin/index';
import { Nest } from '@/api/admin/nest';
import http, { QueryBuilderParams, withQueryBuilderParams } from '@/api/http';
import { AdminTransformers } from '@/api/admin/transformers';
import { AxiosError } from 'axios';
import { useRouteMatch } from 'react-router-dom';
import useSWR, { SWRResponse } from 'swr';

export interface Egg extends Model {
    id: number;
    uuid: UUID;
    nestId: number;
    author: string;
    name: string;
    description: string | null;
    features: string[] | null;
    dockerImages: string[];
    configFiles: Record<string, any> | null;
    configStartup: Record<string, any> | null;
    configStop: string | null;
    configFrom: number | null;
    startup: string;
    scriptContainer: string;
    copyScriptFrom: number | null;
    scriptEntry: string;
    scriptIsPrivileged: boolean;
    scriptInstall: string | null;
    createdAt: Date;
    updatedAt: Date;
    relationships: {
        nest?: Nest;
        variables?: EggVariable[];
    };
}

export interface EggVariable extends Model {
    id: number;
    eggId: number;
    name: string;
    description: string;
    environmentVariable: string;
    defaultValue: string;
    isUserViewable: boolean;
    isUserEditable: boolean;
    // isRequired: boolean;
    rules: string;
    createdAt: Date;
    updatedAt: Date;
}

/**
 * A standard API response with the minimum viable details for the frontend
 * to correctly render a egg.
 */
type LoadedEgg = WithRelationships<Egg, 'nest' | 'variables'>;

/**
 * Gets a single egg from the database and returns it.
 */
export const getEgg = async (id: number | string): Promise<LoadedEgg> => {
    const { data } = await http.get(`/api/application/eggs/${id}`, {
        params: {
            include: [ 'nest', 'variables' ],
        },
    });

    return withRelationships(AdminTransformers.toEgg(data), 'nest', 'variables');
};

export const searchEggs = async (nestId: number, params: QueryBuilderParams<'name'>): Promise<WithRelationships<Egg, 'variables'>[]> => {
    const { data } = await http.get(`/api/application/nests/${nestId}/eggs`, {
        params: {
            ...withQueryBuilderParams(params),
            include: [ 'variables' ],
        },
    });

    return data.data.map(AdminTransformers.toEgg);
};

export const exportEgg = async (eggId: number): Promise<Record<string, any>> => {
    const { data } = await http.get(`/api/application/eggs/${eggId}/export`);
    return data;
};

/**
 * Returns an SWR instance by automatically loading in the server for the currently
 * loaded route match in the admin area.
 */
export const useEggFromRoute = (): SWRResponse<LoadedEgg, AxiosError> => {
    const { params } = useRouteMatch<{ id: string }>();

    return useSWR(`/api/application/eggs/${params.id}`, async () => getEgg(params.id), {
        revalidateOnMount: false,
        revalidateOnFocus: false,
    });
};
