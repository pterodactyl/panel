import type { UserData } from '@/api/account/types';
import type { SiteSettings } from '@/api/settings/types';
import type { DevelopmentPayload } from '@/extensions/development';
import type { SiteExtensionEntry } from '@/extensions/registry';

export type ThemeScheme = 'dark' | 'light';
export type ThemeSettingValue = string | number | boolean | null;

/** The active theme as the server renders it; `classic` with an empty chain when none is active. */
export interface ThemeBootstrap {
    id: string;
    /** Parent first, excluding classic; `development` is set while `p:extension:dev` serves an entry. */
    chain: {
        id: string;
        version: string;
        entry: string | null;
        development?: DevelopmentPayload | null;
    }[];
    settings: Record<string, ThemeSettingValue>;
    scheme: ThemeScheme;
    schemes: ThemeScheme[];
    safeMode: boolean;
    preview: boolean;
}

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings & {
        extensions?: SiteExtensionEntry[];
        theme?: Partial<ThemeBootstrap>;
    };
    PterodactylUser?: {
        uuid: string;
        username: string;
        email: string;
        root_admin: boolean;
        use_totp: boolean;
        language: string;
        avatar?: string | null;
        updated_at: string;
        created_at: string;
    };
}

export const getBootstrapUser = (): UserData | undefined => {
    const user = (window as ExtendedWindow).PterodactylUser;

    return user
        ? {
              uuid: user.uuid,
              username: user.username,
              email: user.email,
              language: user.language,
              avatar: user.avatar ?? null,
              rootAdmin: user.root_admin,
              useTotp: user.use_totp,
              createdAt: new Date(user.created_at),
              updatedAt: new Date(user.updated_at),
          }
        : undefined;
};

let sessionEnded = false;

export const endBootstrapSession = (): void => {
    sessionEnded = true;
};

export const hasBootstrapSession = (): boolean => !sessionEnded && !!getBootstrapUser()?.uuid;

export const getBootstrapSiteSettings = (): SiteSettings | undefined => (window as ExtendedWindow).SiteConfiguration;

export const getBootstrapExtensions = (): SiteExtensionEntry[] =>
    (window as ExtendedWindow).SiteConfiguration?.extensions ?? [];

const classicTheme: ThemeBootstrap = {
    id: 'classic',
    chain: [],
    settings: {},
    scheme: 'dark',
    schemes: ['dark'],
    safeMode: false,
    preview: false,
};

export const getBootstrapTheme = (): ThemeBootstrap => ({
    ...classicTheme,
    ...(window as ExtendedWindow).SiteConfiguration?.theme,
});

export type ThemeChainEntry = ThemeBootstrap['chain'][number];

export const getBootstrapThemeChain = (): ThemeChainEntry[] => getBootstrapTheme().chain;
