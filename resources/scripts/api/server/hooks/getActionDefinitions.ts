import http from '@/api/http';

export interface ActionConfigSchemaField {
    type: string;
    required: boolean;
    label: string;
    input: string;
    options?: Record<string, string>;
    validate?: string;
}

export interface ActionDefinition {
    key: string;
    name: string;
    description: string;
    config_schema: ActionConfigSchemaField[];
}

export const rawDataToActionDefinition = (data: any): ActionDefinition => ({
    key: data.key,
    name: data.name,
    description: data.description,
    config_schema: data.config_schema,
});

export default async (uuid: string): Promise<ActionDefinition[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/hooks/action-definitions`, {});
    console.log(data.data);
    console.log('Received From Actions');
    return (data.data || []).map((row: any) => rawDataToActionDefinition(row));
};
