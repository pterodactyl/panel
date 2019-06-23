import { FlashMessageType } from '@/components/MessageBox';
import { Action } from 'easy-peasy';

export interface ApplicationState {
    flashes: FlashState;
    user: UserState;
}

export interface FlashState {
    items: FlashMessage[];
    addFlash: Action<FlashState, FlashMessage>;
    clearFlashes: Action<FlashState>;
}

export interface UserState {
    data?: UserData;
    setUserData: Action<UserState, UserData>;
}

export interface UserData {
    uuid: string;
    username: string;
    email: string;
    language: string;
    rootAdmin: boolean;
    useTotp: boolean;
    createdAt: Date;
    updatedAt: Date;
}

export interface FlashMessage {
    id?: string;
    type: FlashMessageType;
    title?: string;
    message: string;
}
