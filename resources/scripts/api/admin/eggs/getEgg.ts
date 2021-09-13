import http, { FractalResponseData } from '@/api/http';

export interface Egg {
    id: number;
    uuid: string;
    nestId: number;
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

export const rawDataToEgg = ({ attributes }: FractalResponseData): Egg => ({
    id: attributes.id,
    uuid: attributes.uuid,
    nestId: attributes.nest_id,
    author: attributes.author,
    name: attributes.name,
    description: attributes.description,
    features: attributes.features,
    dockerImages: attributes.docker_images,
    configFiles: attributes.config_files,
    configStartup: attributes.config_startup,
    configLogs: attributes.config_logs,
    configStop: attributes.config_stop,
    configFrom: attributes.config_from,
    startup: attributes.startup,
    copyScriptFrom: attributes.copy_script_from,
    scriptContainer: attributes.script?.container,
    scriptEntry: attributes.script?.entry,
    scriptIsPrivileged: attributes.script?.privileged,
    scriptInstall: attributes.script?.install,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),
});

export default (id: number): Promise<Egg> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/eggs/${id}`)
            .then(({ data }) => resolve(rawDataToEgg(data)))
            .catch(reject);
    });
};
