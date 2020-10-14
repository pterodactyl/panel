import http from '@/api/http';

export default async (code: string): Promise<string[]> => {
    const { data } = await http.post('/api/client/account/two-factor', { code });

    return data.attributes.tokens;
};
