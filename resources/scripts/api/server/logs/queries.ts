import { clientGetServerLogsOptions } from '@/api/generated/@tanstack/react-query.gen';
import type { ClientGetServerLogsData, Options } from '@/api/generated';

/** The most console lines the panel returns for one read. */
export const SERVER_LOG_LINES_MAX = 100;

const clampLines = (lines: number) => Math.min(Math.max(Math.trunc(lines) || 1, 1), SERVER_LOG_LINES_MAX);

const serverLogsInput = (uuid: string, lines: number): Options<ClientGetServerLogsData> => ({
    path: { server_uuid: uuid },
    query: { lines: clampLines(lines) },
});

export const serverLogsQueryOptions = (
    uuid: string,
    lines = SERVER_LOG_LINES_MAX
): ReturnType<typeof clientGetServerLogsOptions> => clientGetServerLogsOptions(serverLogsInput(uuid, lines));
