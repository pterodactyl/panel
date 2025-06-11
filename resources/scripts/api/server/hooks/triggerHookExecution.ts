import http from '@/api/http';

export default async (server: string, hook: number): Promise<void> =>
    await http.post(`/api/client/servers/${server}/hooks/${hook}/execute`);
