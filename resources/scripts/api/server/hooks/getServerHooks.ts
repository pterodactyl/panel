import http from '@/api/http';
export interface Hook {
    id?: number;
    name: string;
    enabled: boolean;
    createdAt: Date;
    updatedAt: Date;

    trigger: Trigger;
    action: Action;
}
export interface Trigger {
    type: string;
    config: string;
}
export interface Action {
    type: string;
    config: string;
}
export const rawDataToServerAction = (data: any): Action => ({
    type: data.type,
    config: data.config,
});
export const rawDataToServerTrigger = (data: any): Trigger => ({
    type: data.type,
    config: data.config,
});
export const rawDataToServerHook = (data: any): Hook => ({
    id: data.id,
    name: data.name,
    enabled: data.enabled,
    createdAt: new Date(data.created_at),
    updatedAt: new Date(data.updated_at),
    action: rawDataToServerAction(data.relationships?.action?.attributes ?? []),
    trigger: rawDataToServerTrigger(data.relationships?.trigger?.attributes ?? []),
});

export default async (uuid: string): Promise<Hook[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/hooks`, {});
    return (data.data || []).map((row: any) => rawDataToServerHook(row.attributes));
};
