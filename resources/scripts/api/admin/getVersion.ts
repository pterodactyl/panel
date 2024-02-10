import http from '@/api/http';

export interface VersionData {
    panel: {
        current: string;
        latest: string;
    };

    wings: {
        latest: string;
    };

    git: string | null;
}

export default (): Promise<VersionData> => {
    return new Promise((resolve, reject) => {
        http.get('/api/application/version')
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
