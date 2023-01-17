import type { AxiosError } from 'axios';
import { useParams } from 'react-router-dom';
import type { SWRResponse } from 'swr';
import useSWR from 'swr';

import type { Model, UUID, WithRelationships } from '@/api/admin/index';
import { withRelationships } from '@/api/admin/index';
import type { Nest } from '@/api/admin/nest';
import type { QueryBuilderParams } from '@/api/http';
import http, { withQueryBuilderParams } from '@/api/http';
import { Transformers } from '@definitions/admin';

export interface Egg extends Model {
    id: number;
    uuid: UUID;
    nestId: number;
    author: string;
    name: string;
    description: string | null;
    features: string[] | null;
    dockerImages: Record<string, string>;
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
            include: ['nest', 'variables'],
        },
    });

    return withRelationships(Transformers.toEgg(data), 'nest', 'variables');
};

export const searchEggs = async (
    nestId: number,
    params: QueryBuilderParams<'name'>,
): Promise<WithRelationships<Egg, 'variables'>[]> => {
    const { data } = await http.get(`/api/application/nests/${nestId}/eggs`, {
        params: {
            ...withQueryBuilderParams(params),
            include: ['variables'],
        },
    });

    return data.data.map(Transformers.toEgg);
};

export const exportEgg = async (eggId: number): Promise<string> => {
    const { data } = await http.get(`/api/application/eggs/${eggId}/export`);
    return data;
};

/**
 * Returns an SWR instance by automatically loading in the server for the currently
 * loaded route match in the admin area.
 */
export const useEggFromRoute = (): SWRResponse<LoadedEgg, AxiosError> => {
    const params = useParams<'id'>();

    return useSWR(`/api/application/eggs/${params.id}`, async () => getEgg(Number(params.id)), {
        revalidateOnMount: false,
        revalidateOnFocus: false,
    });
};
