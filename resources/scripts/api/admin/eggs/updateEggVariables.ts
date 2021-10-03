import http from '@/api/http';
import { EggVariable, rawDataToEggVariable } from '@/api/admin/eggs/getEgg';

export default (eggId: number, variables: Omit<EggVariable, 'eggId' | 'createdAt' | 'updatedAt'>[]): Promise<EggVariable> => {
    return new Promise((resolve, reject) => {
        http.patch(
            `/api/application/eggs/${eggId}/variables`,
            variables.map(variable => {
                return {
                    id: variable.id,
                    name: variable.name,
                    description: variable.description,
                    env_variable: variable.envVariable,
                    default_value: variable.defaultValue,
                    user_viewable: variable.userViewable,
                    user_editable: variable.userEditable,
                    rules: variable.rules,
                };
            }),
        )
            .then(({ data }) => resolve((data.data || []).map(rawDataToEggVariable)))
            .catch(reject);
    });
};
