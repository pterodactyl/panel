import { action } from 'easy-peasy';
import { FlashState } from '@/state/types';

const flashes: FlashState = {
    items: [],
    addFlash: action((state, payload) => {
        state.items.push(payload);
    }),
    clearFlashes: action(state => {
        state.items = [];
    }),
};

export default flashes;
