import { Action, action } from 'easy-peasy';
import { FlashMessageType } from '@/components/MessageBox';
import { httpErrorToHuman } from '@/api/http';

export interface FlashStore {
    items: FlashMessage[];
    addFlash: Action<FlashStore, FlashMessage>;
    addError: Action<FlashStore, { message: string; key?: string }>;
    clearAndAddHttpError: Action<FlashStore, { error: any, key?: string }>;
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

    addError: action((state, payload) => {
        state.items.push({ type: 'error', title: 'Error', ...payload });
    }),

    clearAndAddHttpError: action((state, { key, error }) => {
        state.items = [ { type: 'error', title: 'Error', key, message: httpErrorToHuman(error) } ];
    }),

    clearFlashes: action((state, payload) => {
        state.items = payload ? state.items.filter(flashes => flashes.key !== payload) : [];
    }),
};

export default flashes;
