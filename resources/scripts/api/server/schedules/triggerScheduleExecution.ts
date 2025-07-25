import http from '@/api/http';

export default async (server: string, schedule: number): Promise<void> =>
    await http.post(`/api/client/servers/${server}/schedules/${schedule}/execute`);
