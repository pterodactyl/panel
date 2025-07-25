import http from '@/api/http';

export default async (uuid: string, image: string): Promise<void> => {
    await http.put(`/api/client/servers/${uuid}/settings/docker-image`, { docker_image: image });
};
