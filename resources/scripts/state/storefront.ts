import { action, Action } from 'easy-peasy';

export interface StorefrontSettings {
    enabled: boolean;
    currency: string;
    renewals: {
        cost: number;
        days: number;
    };
    editing: {
        enabled: boolean;
    };
    referrals: {
        enabled: boolean;
        reward: number;
        days: number;
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
        paypal: boolean;
        stripe: boolean;
    };
    earn: {
        enabled: boolean;
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
