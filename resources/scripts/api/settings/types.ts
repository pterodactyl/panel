export interface SiteSettings {
    name: string;
    logo?: string | null;
    locale: string;
    userAvatarsEnabled: boolean;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
}
