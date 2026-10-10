export interface SiteSettings {
    name: string;
    logo?: string | null;
    locale: string;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
}
