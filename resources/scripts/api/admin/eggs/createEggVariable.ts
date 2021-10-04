import http from '@/api/http';
import { EggVariable, rawDataToEggVariable } from '@/api/admin/eggs/getEgg';

export default (eggId: number, variable: Omit<EggVariable, 'id' | 'eggId' | 'createdAt' | 'updatedAt'>): Promise<EggVariable> => {
    return new Promise((resolve, reject) => {
        http.post(
            `/api/application/eggs/${eggId}/variables`,
            {
                name: variable.name,
                description: variable.description,
                env_variable: variable.envVariable,
                default_value: variable.defaultValue,
                user_viewable: variable.userViewable,
                user_editable: variable.userEditable,
                rules: variable.rules,
            },
        )
            .then(({ data }) => resolve(rawDataToEggVariable(data)))
            .catch(reject);
    });
};
