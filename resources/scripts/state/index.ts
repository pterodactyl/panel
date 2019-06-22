import { action, createStore } from 'easy-peasy';
import { ApplicationState } from '@/state/types';

const state: ApplicationState = {
    flashes: {
        items: [],
        addFlash: action((state, payload) => {
            state.items.push(payload);
        }),
        clearFlashes: action(state => {
            state.items = [];
        }),
    },
};

export const store = createStore(state);
