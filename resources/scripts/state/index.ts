import { createStore } from 'easy-peasy';
import flashes, { FlashStore } from '@/state/flashes';
import user, { UserStore } from '@/state/user';

export interface ApplicationStore {
    flashes: FlashStore;
    user: UserStore;
}

const state: ApplicationStore = {
    flashes,
    user,
};

export const store = createStore(state);
