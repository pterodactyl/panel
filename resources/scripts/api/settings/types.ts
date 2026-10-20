export interface SiteSettings {
    name: string;
    locale: string;
    userAvatarsEnabled: boolean;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
}
