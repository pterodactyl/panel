import { action, Action } from 'easy-peasy';

export interface StorefrontSettings {
    enabled: string;
    cost: {
        cpu: number;
        memory: number;
        disk: number;
        slot: number;
        port: number;
        backup: number;
        database: number;
    }
    paypal: {
        enabled: string;
    }
    stripe: {
        enabled: string;
    }
}

export interface StorefrontStore {
    data?: StorefrontSettings;
    setStorefront: Action<StorefrontStore, StorefrontSettings>;
}

const storefront: StorefrontStore = {
    data: undefined,

    setStorefront: action((state, payload) => {
        state.data = payload;
    }),
};

export default storefront;
