import i18next from 'i18next';
import { initReactI18next } from 'react-i18next';
import { translationOptions } from '@/i18nConfig';
import english from '../lang/en.json';

export function initTestTranslations(): void {
    if (i18next.isInitialized) {
        return;
    }

    void i18next
        .use(initReactI18next)
        .init({ ...translationOptions, lng: 'en', initAsync: false, resources: { en: { translation: english } } });
}
