import { useRef, type KeyboardEvent } from 'react';
import { SocketRequest } from '@/components/server/events';
import type { Websocket } from '@/plugins/Websocket';
import { usePersistedState } from '@/plugins/usePersistedState';

export const useConsoleCommandHistory = (serverId: string, socket: Websocket | null) => {
    const [history = [], setHistory] = usePersistedState<string[]>(`${serverId}:command_history`, []);
    const historyIndex = useRef(-1);

    return (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'ArrowUp') {
            const newIndex = Math.min(historyIndex.current + 1, history.length - 1);

            historyIndex.current = newIndex;
            e.currentTarget.value = history[newIndex] || '';

            // Keep the cursor at the end of the selected command.
            e.preventDefault();
        }

        if (e.key === 'ArrowDown') {
            const newIndex = Math.max(historyIndex.current - 1, -1);

            historyIndex.current = newIndex;
            e.currentTarget.value = history[newIndex] || '';
        }

        const command = e.currentTarget.value;

        if (e.key === 'Enter' && command.length > 0) {
            setHistory((prevHistory) => [command, ...(prevHistory ?? [])].slice(0, 32));
            historyIndex.current = -1;

            socket?.send(SocketRequest.SEND_COMMAND, command);
            e.currentTarget.value = '';
        }
    };
};
