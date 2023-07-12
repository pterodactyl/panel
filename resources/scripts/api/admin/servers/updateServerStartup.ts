import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export interface Values {
    startup: string;
    environment: Record<string, any>;
    eggId: number;
    image: string;
    skipScripts: boolean;
}

export default (id: number, values: Partial<Values>, include: string[] = []): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.patch(
            `/api/application/servers/${id}/startup`,
            {
                startup: values.startup !== '' ? values.startup : null,
                environment: values.environment,
                egg_id: values.eggId,
                image: values.image,
                skip_scripts: values.skipScripts,
            },
            { params: { include: include.join(',') } },
        )
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
