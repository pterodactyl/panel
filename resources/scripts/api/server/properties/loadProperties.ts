import http from '@/api/http';

export interface PropertiesResponse {
    properties: {
        key: string;
        value: string;
        type: string;
        values: string[];
    }[];
    initial: any[];
}

export default async (uuid: string): Promise<PropertiesResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/properties`);

    return data || [];
};
