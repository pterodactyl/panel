import { VIEW_PAGES } from '../pages';
import { defineView } from '../registry';
import type { AuthPageModel } from './types';
import './login';
import './password';

const pageModel: AuthPageModel = Object.freeze({});
const page = { use: (): AuthPageModel => pageModel, fixtures: { default: () => pageModel } };

export const authPageViews = [
    defineView('page.auth.login', { ...VIEW_PAGES['page.auth.login'], ...page }),
    defineView('page.auth.checkpoint', { ...VIEW_PAGES['page.auth.checkpoint'], ...page }),
    defineView('page.auth.password', { ...VIEW_PAGES['page.auth.password'], ...page }),
    defineView('page.auth.passwordReset', { ...VIEW_PAGES['page.auth.passwordReset'], ...page }),
];
