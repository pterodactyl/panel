import http from '@/api/http';

export interface Nest {
    id: number;
    name: string;
}

export const rawDataToNest = (data: any): Nest => ({
    id: data.id,
    name: data.name,
});

export const getNests = async (): Promise<Nest[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/store/nests')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToNest(d.attributes))))
            .catch(reject);
    });
};
