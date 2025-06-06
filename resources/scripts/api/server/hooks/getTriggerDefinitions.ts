import http from '@/api/http';

export interface TriggerConfigSchemaField {
    type: string;
    required: boolean;
    label: string;
    input: string;
    options?: Record<string, string>;
    validate?: string;
}

export interface TriggerDefinition {
    key: string;
    name: string;
    description: string;
    config_schema: TriggerConfigSchemaField[];
}

export const rawDataToTriggerDefinition = (data: any): TriggerDefinition => ({
    key: data.key,
    name: data.name,
    description: data.description,
    config_schema: data.config_schema,
});

export default async (uuid: string): Promise<TriggerDefinition[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/hooks/trigger-definitions`, {});
    return (data.data || []).map((row: any) => rawDataToTriggerDefinition(row));
};
