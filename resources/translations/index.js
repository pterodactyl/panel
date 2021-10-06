import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import PTBR from './pt-BR/pt-BR.json';
import ENUS from './en-US/en-US.json';

const resources = {
    'pt-BR': PTBR,
    'en-US': ENUS
}

i18n
    .use(initReactI18next)
    .init({
        resources,
        lng: navigator.language,
        fallbackLng: 'en-US',
        preload: true,
        interpolation: {
            escapeValue: false,
        }
    })


export default i18n;