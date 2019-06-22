import { FlashMessageType } from '@/components/MessageBox';
import { Action } from 'easy-peasy';

export interface ApplicationState {
    flashes: FlashState;
}

export interface FlashState {
    items: FlashMessage[];
    addFlash: Action<FlashState, FlashMessage>;
    clearFlashes: Action<FlashState>;
}

export interface FlashMessage {
    id?: string;
    type: FlashMessageType;
    title?: string;
    message: string;
}
