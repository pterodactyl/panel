import { action } from 'easy-peasy';
import { FlashState } from '@/state/types';

const flashes: FlashState = {
    items: [],
    addFlash: action((state, payload) => {
        state.items.push(payload);
    }),
    clearFlashes: action((state, payload) => {
        state.items = payload ? state.items.filter(flashes => flashes.key !== payload) : [];
    }),
};

export default flashes;
