import { Allocation } from '@/api/server/getServer';
import { FractalResponseData } from '@/api/http';

export const rawDataToServerAllocation = (data: FractalResponseData): Allocation => ({
    ip: data.attributes.ip,
    alias: data.attributes.ip_alias,
    port: data.attributes.port,
    isDefault: data.attributes.is_default,
});
