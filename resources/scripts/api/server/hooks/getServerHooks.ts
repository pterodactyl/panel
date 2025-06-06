import http from '@/api/http';
export interface Hook {
    id: number;
    name: string;
    enabled: boolean;
    createdAt: Date;
    updatedAt: Date;

    triggers: Trigger[];
    actions: Action[];
}
export interface Trigger {
    type: string;
    config: string;
}
export interface Action {
    id: number;
    type: string;
    data: string;
    createdAt: Date;
    updatedAt: Date;
}
export const rawDataToServerAction = (data: any): Action => ({
    id: data.id,
    type: data.type,
    data: data.data,
    createdAt: new Date(data.created_at),
    updatedAt: new Date(data.updated_at),
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

    actions: (data.relationships?.actions || []).map((row: any) => rawDataToServerAction(row.attributes)),
    triggers: (data.relationships?.triggers || []).map((row: any) => rawDataToServerTrigger(row.attributes)),
});

export default async (uuid: string): Promise<Hook[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/hooks`, {});
    console.log('Retrieved Backend');
    console.log(data);
    return (data.data || []).map((row: any) => rawDataToServerHook(row.attributes));
};
