import { useEffect, useRef } from 'react';
import { useServerStatus, useSocketConnected, useSocketInstance } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import { useDialogState } from '@/components/elements/dialog';

type ConsolePattern = string | RegExp;

interface ConsolePatternTriggerOptions {
    onTrigger?: (line: string) => void;
}

const matchesPattern = (line: string, pattern: ConsolePattern) =>
    pattern instanceof RegExp ? pattern.test(line) : line.toLowerCase().includes(pattern);

export const useConsolePatternTrigger = (
    patterns: readonly ConsolePattern[],
    options: ConsolePatternTriggerOptions = {}
) => {
    const dialog = useDialogState();
    const { show } = dialog;
    const status = useServerStatus();
    const connected = useSocketConnected();
    const instance = useSocketInstance();
    const onTrigger = useRef(options.onTrigger);

    useEffect(() => {
        onTrigger.current = options.onTrigger;
    }, [options.onTrigger]);

    useEffect(() => {
        if (!connected || !instance || status === 'running') {
            return;
        }

        const listener = (line: string) => {
            if (patterns.some((pattern) => matchesPattern(line, pattern))) {
                onTrigger.current?.(line);
                show();
            }
        };

        instance.addListener(SocketEvent.CONSOLE_OUTPUT, listener);

        return () => {
            instance.removeListener(SocketEvent.CONSOLE_OUTPUT, listener);
        };
    }, [connected, instance, patterns, show, status]);

    return dialog;
};
