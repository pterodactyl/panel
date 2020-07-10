import { Allocation } from '@/api/server/getServer';
import { FractalResponseData } from '@/api/http';

export const rawDataToServerAllocation = (data: FractalResponseData): Allocation => ({
    id: data.attributes.id,
    ip: data.attributes.ip,
    alias: data.attributes.ip_alias,
    port: data.attributes.port,
    notes: data.attributes.notes,
    isDefault: data.attributes.is_default,
});
