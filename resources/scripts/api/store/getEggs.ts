import http from '@/api/http';

export interface Egg {
    id: number;
    name: string;
    dockerImages: string[];
}

export const rawDataToEgg = (data: any): Egg => ({
    id: data.id,
    name: data.name,
    dockerImages: data.docker_images,
});

export const getEggs = async (id?: number): Promise<Egg[]> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/store/eggs', { id })
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToEgg(d.attributes))))
            .catch(reject);
    });
};
