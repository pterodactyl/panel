import http from '@/api/http';

export const restoreServerBackup = async (uuid: string, backup: string, truncate?: boolean): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/backups/${backup}/restore`, {
        truncate,
    });
};
