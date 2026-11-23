import dayjs from 'dayjs';
import advancedFormat from 'dayjs/plugin/advancedFormat';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import relativeTime from 'dayjs/plugin/relativeTime';

// Import dayjs from this module so these plugins are registered.
dayjs.extend(advancedFormat);
dayjs.extend(localizedFormat);
dayjs.extend(relativeTime);

const locales = import.meta.glob<{ default: ILocale }>('../../../node_modules/dayjs/esm/locale/*.js');

const localeLoader = (code: string) => locales[`../../../node_modules/dayjs/esm/locale/${code}.js`];

export async function setDateLocale(language: string): Promise<string> {
    const code = language.toLowerCase().replace('_', '-');

    const load = [code, code.split('-')[0]!]
        .filter((candidate) => candidate !== 'en')
        .map(localeLoader)
        .find((loader) => loader !== undefined);

    if (!load) {
        dayjs.locale('en');

        return 'en';
    }

    const { default: locale } = await load();

    dayjs.locale(locale);

    return locale.name;
}

export default dayjs;
