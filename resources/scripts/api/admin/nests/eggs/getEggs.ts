import http from '@/api/http';
import { rawDataToEgg } from '@/api/transformers';

export interface Egg {
    id: number;
    uuid: string;
    nest_id: number;
    author: string;
    name: string;
    description: string | null;
    features: string[] | null;
    dockerImages: string[];
    configFiles: string | null;
    configStartup: string | null;
    configLogs: string | null;
    configStop: string | null;
    configFrom: number | null;
    startup: string;
    scriptContainer: string;
    copyScriptFrom: number | null;
    scriptEntry: string;
    scriptIsPrivileged: boolean;
    scriptInstall: string | null;
    createdAt: Date;
    updatedAt: Date;
}

export default (id: number): Promise<Egg[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nests/${id}`)
            .then(({ data }) => resolve((data.data || []).map(rawDataToEgg)))
            .catch(reject);
    });
};
