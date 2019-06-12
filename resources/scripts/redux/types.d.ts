import { FlashMessageType } from '@/components/MessageBox';

export interface ReduxReducerAction {
    type: string;
    payload?: any;
}

export interface FlashMessage {
    id?: string;
    type: FlashMessageType;
    title?: string;
    message: string;
}

export interface ReduxState {
    flashes: FlashMessage[];
}
