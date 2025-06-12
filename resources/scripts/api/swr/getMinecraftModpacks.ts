import useSWR from 'swr';

import { getPaginationSet, PaginatedResult } from '@/api/http';
import http from '@/api/http';
import { ServerContext } from '@/state/server';

export interface Modpack {
    id: string;
    name: string;
    description: string | null;
    url: string | null;
    iconUrl: string | null;
}

export type ModpackProvider = 'atlauncher' | 'curseforge' | 'feedthebeast' | 'modrinth' | 'technic' | 'voidswrath';

export const rawDataToModpack = (data: any): Modpack => ({
    id: data.id,
    name: data.name,
    description: data.description,
    url: data.url,
    iconUrl: data.icon_url,
});

type ModpackResponse = PaginatedResult<Modpack> & {
    installed_modpack: (Modpack & { provider: string }) | null;
};

export default (provider: ModpackProvider, searchQuery: string, pageSize: number, page: number) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<ModpackResponse>(['server:modpacks', uuid, provider, searchQuery, pageSize, page], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/minecraft-modpacks`, {
            params: {
                provider,
                search_query: searchQuery,
                page_size: pageSize,
                page,
            },
        });

        return {
            items: (data.data || []).map(rawDataToModpack),
            pagination: getPaginationSet(data.meta.pagination),
            installed_modpack: data.meta.installed_modpack
                ? { iconUrl: data.meta.installed_modpack.icon_url, ...data.meta.installed_modpack }
                : null,
        };
    });
};
