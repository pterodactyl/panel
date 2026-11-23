import dayjs from 'dayjs';
import advancedFormat from 'dayjs/plugin/advancedFormat';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import relativeTime from 'dayjs/plugin/relativeTime';

// Import dayjs from this module so these plugins are registered.
dayjs.extend(advancedFormat);
dayjs.extend(localizedFormat);
dayjs.extend(relativeTime);

// The panel has always shown English times on a 24-hour clock.
dayjs.Ls.en!.formats = {
    LT: 'HH:mm',
    LTS: 'HH:mm:ss',
    L: 'MM/DD/YYYY',
    LL: 'MMMM D, YYYY',
    LLL: 'MMMM D, YYYY HH:mm',
    LLLL: 'dddd, MMMM D, YYYY HH:mm',
};

const locales = import.meta.glob<{ default: ILocale }>('../../../node_modules/dayjs/esm/locale/*.js');

const localeLoader = (code: string) => locales[`../../../node_modules/dayjs/esm/locale/${code}.js`];

let latest = 0;

export async function setDateLocale(language: string): Promise<string> {
    const request = ++latest;
    const code = language.toLowerCase().replaceAll('_', '-');
    const [base, ...subtags] = code.split('-');
    const region = subtags.find((subtag) => subtag.length !== 4);

    const load = [code, region ? `${base}-${region}` : code, base!]
        .filter((candidate) => candidate !== 'en')
        .map(localeLoader)
        .find((loader) => loader !== undefined);

    const locale = load ? (await load()).default : null;

    if (request !== latest) {
        return dayjs.locale();
    }

    dayjs.locale(locale ?? 'en');

    return locale?.name ?? 'en';
}

export default dayjs;
