import http, { FractalResponseData } from '@/api/http';

export interface Role {
    id: number;
    name: string;
    description: string | null;
}

export const rawDataToRole = ({ attributes }: FractalResponseData): Role => ({
    id: attributes.id,
    name: attributes.name,
    description: attributes.description,
});

export default (): Promise<Role[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/application/roles')
            .then(({ data }) => resolve((data.data || []).map(rawDataToRole)))
            .catch(reject);
    });
};
