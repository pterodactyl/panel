import http from '@/api/http';
import { EggVariable } from '@/api/admin/egg';
import { Transformers } from '@definitions/admin';

export default async (
    eggId: number,
    variables: Omit<EggVariable, 'eggId' | 'createdAt' | 'updatedAt'>[],
): Promise<EggVariable[]> => {
    const { data } = await http.patch(
        `/api/application/eggs/${eggId}/variables`,
        variables.map(variable => ({
            id: variable.id,
            name: variable.name,
            description: variable.description,
            env_variable: variable.environmentVariable,
            default_value: variable.defaultValue,
            user_viewable: variable.isUserViewable,
            user_editable: variable.isUserEditable,
            rules: variable.rules,
        })),
    );

    return data.data.map(Transformers.toEggVariable);
};
