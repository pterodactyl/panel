import http from '@/api/http';

export default async (uuid: string, id: number): Promise<void> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/plugins/install/${id}`);

    return data.data || [];
};
