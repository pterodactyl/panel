import 'react-i18next';
import auth from '@/locales/en/auth';
import dashboard from '@/locales/en/dashboard';
import elements from '@/locales/en/elements';
import server from '@/locales/en/server';

declare module 'react-i18next' {
    interface Resources {
        auth: typeof auth;
        dashboard: typeof dashboard;
        elements: typeof elements;
        server: typeof server;
    }
}
