import http from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

type Egg2 = Omit<Omit<Partial<Egg>, 'configFiles'>, 'configStartup'> & { configFiles: string; configStartup: string };

export default (egg: Partial<Egg2>): Promise<Egg> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/eggs', {
            nest_id: egg.nestId,
            name: egg.name,
            description: egg.description,
            features: egg.features,
            docker_images: egg.dockerImages,
            config_files: egg.configFiles,
            config_startup: egg.configStartup,
            config_stop: egg.configStop,
            config_from: egg.configFrom,
            startup: egg.startup,
            script_container: egg.scriptContainer,
            copy_script_from: egg.copyScriptFrom,
            script_entry: egg.scriptEntry,
            script_is_privileged: egg.scriptIsPrivileged,
            script_install: egg.scriptInstall,
        })
            .then(({ data }) => resolve(rawDataToEgg(data)))
            .catch(reject);
    });
};
