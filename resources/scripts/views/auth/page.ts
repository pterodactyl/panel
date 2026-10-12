import type { AuthPageModel } from './types';

const pageModel: AuthPageModel = Object.freeze({});

/** The implementation of every sign-in page, which only places its form. */
export const authPage = { use: (): AuthPageModel => pageModel, fixtures: { default: () => pageModel } };
