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

export const getEggs = async (): Promise<Egg[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/store/eggs/1')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToEgg(d.attributes))))
            .catch(reject);
    });
};
