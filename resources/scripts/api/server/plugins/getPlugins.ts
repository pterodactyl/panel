import http from '@/api/http';

export interface Plugin {
    plugins: any[];
}

export default async (uuid: string, query: string): Promise<Plugin> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/plugins`, {
        query,
    });

    return data.data || [];
};
