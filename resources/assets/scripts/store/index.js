import auth from './modules/auth';

export default {
    strict: process.env.NODE_ENV !== 'production',
    modules: { auth },
};
