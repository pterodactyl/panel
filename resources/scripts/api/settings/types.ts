export interface SiteSettings {
    name: string;
    locale: string;
    languages?: Record<string, string>;
    translations?: string | null;
    userAvatarsEnabled: boolean;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
}
