import { FlashMessageType } from '@/components/MessageBox';
import { Action } from 'easy-peasy';
import { UserState } from '@/state/models/user';
import { ServerState } from '@/state/models/server';

export interface ApplicationState {
    flashes: FlashState;
    user: UserState;
    server: ServerState;
}

export interface FlashState {
    items: FlashMessage[];
    addFlash: Action<FlashState, FlashMessage>;
    clearFlashes: Action<FlashState, string | void>;
}

export interface FlashMessage {
    id?: string;
    key?: string;
    type: FlashMessageType;
    title?: string;
    message: string;
}
