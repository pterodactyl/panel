import { Model } from '@/api/admin/index';
import http from '@/api/http';
import Transformers from '../definitions/admin/transformers';
import useSWR from 'swr';

export interface Settings extends Model {
    general: GeneralSettings;
    mail: MailSettings;
    security: SecuritySettings;
}

export interface GeneralSettings {
    name: string;
    languages: Language[];
}

type Language = {
    [key: string]: string;
};

export type LanguageKey = 'en';

export interface MailSettings {
    host: string;
    port: number;
    username: string;
    password: string;
    encryption: string;
    fromAddress: string;
    fromName: string;
}

export interface SecuritySettings {
    recaptcha: {
        enabled: boolean;
        siteKey: string;
        secretKey: string;
    };
    '2faEnabled': boolean;
}

export const getSettings = () => {
    return useSWR<Settings>('settings', async () => {
        const { data } = await http.get(`/api/application/settings`);

        return Transformers.toSettings(data);
    });
};
