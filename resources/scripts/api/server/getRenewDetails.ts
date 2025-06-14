import http from '@/api/http';
import { RenewDetailsResponse } from '@/components/server/settings/RenewServerBox';

export default async (uuid: string): Promise<RenewDetailsResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/settings/renew`);

    return (data.data || []);
};
