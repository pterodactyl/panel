import { createStore } from 'easy-peasy';
import { ApplicationState } from '@/state/types';
import flashes from '@/state/models/flashes';
import user from '@/state/models/user';
import server from '@/state/models/server';

const state: ApplicationState = {
    flashes,
    user,
    server,
};

export const store = createStore(state);
