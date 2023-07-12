import http from '@/api/http';
import { EggVariable } from '@/api/admin/egg';
import { Transformers } from '@definitions/admin';

export type CreateEggVariable = Omit<EggVariable, 'id' | 'eggId' | 'createdAt' | 'updatedAt' | 'relationships'>;

export default async (eggId: number, variable: CreateEggVariable): Promise<EggVariable> => {
    const { data } = await http.post(`/api/application/eggs/${eggId}/variables`, {
        name: variable.name,
        description: variable.description,
        env_variable: variable.environmentVariable,
        default_value: variable.defaultValue,
        user_viewable: variable.isUserViewable,
        user_editable: variable.isUserEditable,
        rules: variable.rules,
    });

    return Transformers.toEggVariable(data);
};
