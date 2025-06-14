import http from '@/api/http';
import { CategoriesResponse } from '@/components/shop/CategoriesContainer';

export default async (): Promise<CategoriesResponse> => {
    const { data } = await http.get('/api/client/shop/categories');

    return (data.data || []);
};
