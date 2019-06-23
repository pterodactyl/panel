import { createStore } from 'easy-peasy';
import { ApplicationState } from '@/state/types';
import flashes from '@/state/models/flashes';
import user from '@/state/models/user';

const state: ApplicationState = {
    flashes,
    user,
};

export const store = createStore(state);
