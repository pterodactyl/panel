import { Action, action } from 'easy-peasy';
import { FlashMessageType } from '@/components/MessageBox';

export interface FlashStore {
    items: FlashMessage[];
    addFlash: Action<FlashStore, FlashMessage>;
    clearFlashes: Action<FlashStore, string | void>;
}

export interface FlashMessage {
    id?: string;
    key?: string;
    type: FlashMessageType;
    title?: string;
    message: string;
}

const flashes: FlashStore = {
    items: [],
    addFlash: action((state, payload) => {
        state.items.push(payload);
    }),
    clearFlashes: action((state, payload) => {
        state.items = payload ? state.items.filter(flashes => flashes.key !== payload) : [];
    }),
};

export default flashes;
