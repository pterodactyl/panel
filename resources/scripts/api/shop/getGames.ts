import http from '@/api/http';
import { GamesResponse } from '@/components/shop/GamesContainer';

export default async (shortUrl: string): Promise<GamesResponse> => {
    const { data } = await http.get(`/api/client/shop/categories/${shortUrl}`);

    return (data.data || []);
};
