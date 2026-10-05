import { useMutation } from '@tanstack/react-query';

import {
    clientSendPowerActionMutation,
    clientSendServerCommandMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    ClientSendPowerActionData,
    ClientSendPowerActionRequest,
    ClientSendServerCommandData,
    Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type ServerPowerSignal = ClientSendPowerActionRequest['signal'];

export const sendPowerActionInput = (uuid: string, signal: ServerPowerSignal): Options<ClientSendPowerActionData> => ({
    path: { server_uuid: uuid },
    body: { signal },
});

export const sendServerCommandInput = (uuid: string, command: string): Options<ClientSendServerCommandData> => ({
    path: { server_uuid: uuid },
    body: { command },
});

// For callers without a Wings websocket connection.
export const useSendPowerAction = () =>
    useMutation({
        ...clientSendPowerActionMutation(),
        onError: (error) => notifyHttpError(error, 'Unable to send power action'),
    });

export const useSendServerCommand = () =>
    useMutation({
        ...clientSendServerCommandMutation(),
        onError: (error) => notifyHttpError(error, 'Unable to send command'),
    });
