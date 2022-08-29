import http from '@/api/http';

export default async (code: string, password: string): Promise<string[]> => {
    const { data } = await http.post('/api/client/account/two-factor', { code, password });

    return data.attributes.tokens;
};
