import http, { FractalResponseData } from '@/api/http';

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

export const rawDataToEgg = ({ attributes }: FractalResponseData): Egg => ({
    id: attributes.id,
    uuid: attributes.uuid,
    nest_id: attributes.nest_id,
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
    scriptContainer: attributes.script_container,
    copyScriptFrom: attributes.copy_script_from,
    scriptEntry: attributes.script_entry,
    scriptIsPrivileged: attributes.script_is_privileged,
    scriptInstall: attributes.script_install,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),
});

export default (nestId: number): Promise<Egg[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nests/${nestId}`)
            .then(({ data }) => resolve((data.data || []).map(rawDataToEgg)))
            .catch(reject);
    });
};
