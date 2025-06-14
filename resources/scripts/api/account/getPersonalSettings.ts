import http from '@/api/http';
import { PersonalSettingsResponse } from '@/components/dashboard/PersonalSettingsContainer';

export default async (): Promise<PersonalSettingsResponse> => {
    const { data } = await http.get('/api/client/account/personal');
    return (data.data || []);
};
