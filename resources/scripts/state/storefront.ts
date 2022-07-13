import { action, Action } from 'easy-peasy';

export interface StorefrontSettings {
    enabled: string;
    currency: string;
    renewals: {
        cost: number;
    };
    referrals: {
        enabled: string;
        reward: number;
    };
    cost: {
        cpu: number;
        memory: number;
        disk: number;
        slot: number;
        port: number;
        backup: number;
        database: number;
    };
    limit: {
        cpu: number;
        memory: number;
        disk: number;
        port: number;
        backup: number;
        database: number;
    };
    gateways: {
        paypal: string;
        stripe: string;
    };
    earn: {
        enabled: string;
        amount: number;
    };
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
