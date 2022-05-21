import { createStore } from 'easy-peasy';
import user, { UserStore } from '@/state/user';
import flashes, { FlashStore } from '@/state/flashes';
import settings, { SettingsStore } from '@/state/settings';
import progress, { ProgressStore } from '@/state/progress';
import permissions, { GloablPermissionsStore } from '@/state/permissions';
import storefront, { StorefrontStore } from '@/state/storefront';

export interface ApplicationStore {
    permissions: GloablPermissionsStore;
    flashes: FlashStore;
    user: UserStore;
    settings: SettingsStore;
    progress: ProgressStore;
    storefront: StorefrontStore;
}

const state: ApplicationStore = {
    permissions,
    flashes,
    user,
    settings,
    progress,
    storefront,
};

export const store = createStore(state);
